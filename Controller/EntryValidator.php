<?php

namespace BitForm\Controller;

use BitForm\Context;
use BitForm\Constant\FormMetaConstants as FMC;
use BitForm\Utils\DateTimeUtils;
use BitForm\Utils\JsonUtils;

class EntryValidator
{

    private $formRepository;
    private $formMetaRepository;
    private $entryRepository;
    private $entryDataRepository;

    public function __construct()
    {
        $this->formRepository = Context::$formRepository;
        $this->formMetaRepository = Context::$formMetaRepository;
        $this->entryRepository = Context::$entryRepository;
        $this->entryDataRepository = Context::$entryDataRepository;
    }

    public function anonymousEntry($formId, $entry, $captchaToken)
    {
        $config = $this->formRepository->findConfigById($formId);
        if ($config === null) {
            return __('Invalid form', 'bitform');
        }

        $invalid = $this->validateBaseConfig($config, $formId);
        if ($invalid) {
            return $invalid;
        }

        $invalid = $this->validateCaptcha($config, $formId, $captchaToken);
        if ($invalid) {
            return $invalid;
        }

        return $this->validateEntry($formId, $entry);
    }

    public function anonymousFile($formId)
    {
        $config = $this->formRepository->findConfigById($formId);
        if ($config === null) {
            return __('Invalid form', 'bitform');
        }

        return $this->validateBaseConfig($config, $formId);
    }

    public function validateBaseConfig($config, $formId)
    {
        $isFormOn = FMC::isBaseSettingOn($config, FMC::ON_OFF);
        if (!$isFormOn) {
            return __('Form is closed', 'bitform');
        }

        $isStartEndDateOn = FMC::isBaseSettingOn($config, FMC::START_END_DATE);
        $isStartEndTimeOn = FMC::isBaseSettingOn($config, FMC::START_END_TIME);
        $isMaxOn = FMC::isBaseSettingOn($config, FMC::MAX);
        if (!$isStartEndDateOn && !$isStartEndTimeOn && !$isMaxOn) {
            return false;
        }

        $settings = $this->formMetaRepository->findAllByFormIdAndScope($formId, FMC::SETTING_BASE_PREFIX);

        if ($isStartEndDateOn) {
            $startEndDateStr = FMC::getBaseSetting($settings, FMC::START_END_DATE);
            $startEndDate = JsonUtils::parse($startEndDateStr);
            $now = DateTimeUtils::currentDateTime();
            if ($startEndDate[0] > $now || $startEndDate[1] < $now) {
                return __('Form is closed', 'bitform');
            }
        }

        if ($isStartEndTimeOn) {
            $startEndTimeStr = FMC::getBaseSetting($settings, FMC::START_END_TIME);
            $startEndTime = JsonUtils::parse($startEndTimeStr);
            $now = substr(DateTimeUtils::currentDateTime(), 11);
            if ($startEndTime[0] > $now || $startEndTime[1] < $now) {
                return __('Form is closed', 'bitform');
            }
        }

        if ($isMaxOn) {
            $maxStr = FMC::getBaseSetting($settings, FMC::MAX);
            $max = JsonUtils::parse($maxStr);
            $maxDateRange = DateTimeUtils::getStartEndDateTime($max[0]);
            $count = $this->entryRepository->countByFormIdAndCreatedAt($formId, $maxDateRange[0], $maxDateRange[1]);
            if ($count >= $max[1]) {
                return __('Form is closed', 'bitform');
            }
        }

        return false;
    }

    private function validateCaptcha($config, $formId, $token)
    {
        $isCaptchaOn = FMC::isSettingOn($config, FMC::SETTING_CAPTCHA_PREFIX, FMC::ON_OFF);
        if (!$isCaptchaOn) {
            return false;
        }

        if (!$token) {
            return __('Token is required', 'bitform');
        }

        $settings = $this->formMetaRepository->findAllByFormIdAndScope($formId, FMC::SETTING_CAPTCHA_PREFIX);
        $secret = FMC::getSetting($settings, FMC::SETTING_CAPTCHA_PREFIX, FMC::SECRET);
        if (!$secret) {
            return __('Secret is required', 'bitform');
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $secret,
                'response' => $token
            ]
        ]);
        $body = wp_remote_retrieve_body($response);
        if (!$body) {
            return __('Google server is unreachable', 'bitform');
        }
        $result = JsonUtils::parse($body, true);
        if ($result['success'] !== true || $result['score'] < 0.4) {
            return __('ReCaptcha verification failed. Please submit again, or refresh the page and try again', 'bitform');
        }
        return false;
    }

    private function parseFormJson($formId)
    {
        $json = $this->formRepository->findVarById('json', $formId);
        $jsonArr = JsonUtils::parse($json, true);

        $uniques = [];
        $items = $jsonArr['items'];
        foreach ($items as $item) {
            if ($item['unique']) {
                $uniques[] = $item;
            }
        }

        return [
            'json' => $json,
            'form' => $jsonArr,
            'uniques' => $uniques
        ];
    }

    public function validateEntries($formId, $entries)
    {
        $form = $this->parseFormJson($formId);
        foreach ($entries as $entry) {
            $uniqueError = $this->isDataUnique($formId, $form['uniques'], $entry);
            if (count($uniqueError)) {
                return $uniqueError;
            }
        }
        return false;
    }

    public function validateEntry($formId, $entry)
    {
        $form = $this->parseFormJson($formId);
        $uniqueError = $this->isDataUnique($formId, $form['uniques'], $entry);
        if (count($uniqueError)) {
            return $uniqueError;
        }
        return false;
    }

    private function isDataUnique($formId, $uniques, $entry)
    {
        $error = [];
        foreach ($uniques as $item) {
            $itemId = $item['id'];
            $data = $entry[$itemId];
            if (!$data) continue;
            $count = $this->entryDataRepository->countByFormItemData($formId, $itemId, $data);
            if (!$count) continue;
            $error[$itemId] = ['unique' => $count];
        }
        return $error;
    }
}
