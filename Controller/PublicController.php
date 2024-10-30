<?php

namespace BitForm\Controller;

use BitForm\Context;
use BitForm\Constant\FormMetaConstants as FMC;
use BitForm\Utils\FileUtils;
use BitForm\Utils\JsonUtils;

class PublicController extends AbstractController
{

    private $entryValidator;
    private $entryRepository;
    private $formRepository;
    private $formMetaRepository;
    private $notification;

    public function __construct()
    {
        parent::__construct();
        $this->entryValidator = Context::$entryValidator;
        $this->entryRepository = Context::$entryRepository;
        $this->formRepository = Context::$formRepository;
        $this->formMetaRepository = Context::$formMetaRepository;
        $this->notification = Context::$notification;
    }

    public function registerRoutes()
    {
        $this->registerRoute('/f/(?P<id>\d+)/e', 'POST', 'postEntries', 'anonymous');
        $this->registerRoute('/f/(?P<id>\d+)/f', 'POST', 'postFiles', 'anonymous');
    }

    public function postEntries($request)
    {
        $formId = $this->getPathId($request);
        $captcha = $request->get_param('captcha');
        $entry = $request->get_json_params();
        $invalid = $this->entryValidator->anonymousEntry($formId, $entry, $captcha);
        if ($invalid) {
            $msg = __('Data submit failed', 'bitform');
            return $this->badRequest($msg, $invalid);
        }
        $this->entryRepository->createByFormData($formId, $entry);
        $this->notification->send($formId, $entry);
        return $this->ok();
    }

    public function postFiles($request)
    {
        $formId = $this->getPathId($request);
        $invalid = $this->entryValidator->anonymousFile($formId);
        if ($invalid) {
            $msg = __('Data submit failed', 'bitform');
            return $this->badRequest($msg, $invalid);
        }
        $files = $request->get_file_params();
        $file = $files['file'];
        $originFileName = $file['name'];
        $extension = FileUtils::getExtension($originFileName);
        if (!$extension) {
            $msg = __('Invalid file type', 'bitform');
            return $this->badRequest($msg);
        }
        $fileDir = FileUtils::getFormDir($formId);
        $newFileName = FileUtils::getRandomFileName() . '.' . $extension;
        move_uploaded_file($file['tmp_name'], $fileDir['basedir'] . $newFileName);
        return $newFileName;
    }

    public function anonymousForm($formId)
    {
        $form = $this->formRepository->findById($formId);
        if ($form === null) {
            $msg = __('Invalid form', 'bitform');
            return $this->error($msg);
        }

        $formConfig = $form['config'];
        $invalid = $this->entryValidator->validateBaseConfig($formConfig, $formId);
        if ($invalid) {
            return $this->error($invalid);
        }

        $setting = [];
        $isSubmitRedirectOn = FMC::isSettingOn($formConfig, FMC::SETTING_SUBMIT_PREFIX, FMC::MODE);
        $submitSetting = $this->formMetaRepository->findAllByFormIdAndScope($formId, FMC::SETTING_SUBMIT_PREFIX);
        if ($isSubmitRedirectOn) {
            FMC::setSetting($setting, $submitSetting, FMC::SETTING_SUBMIT_PREFIX, FMC::URL);
        } else {
            FMC::setSetting($setting, $submitSetting, FMC::SETTING_SUBMIT_PREFIX, FMC::HTML);
        }

        $isCaptchaOn = FMC::isSettingOn($formConfig, FMC::SETTING_CAPTCHA_PREFIX, FMC::ON_OFF);
        if ($isCaptchaOn) {
            $captchaSetting = $this->formMetaRepository->findAllByFormIdAndScope($formId, FMC::SETTING_CAPTCHA_PREFIX);
            FMC::setSetting($setting, $captchaSetting, FMC::SETTING_CAPTCHA_PREFIX, FMC::KEY);
        }

        return $this->view($form['json'], $setting);
    }

    public function view($formJson = 'null', $setting = null)
    {
        $recaptchaV3 = null;
        if ($setting) {
            $recaptchaV3 = FMC::getSetting($setting, FMC::SETTING_CAPTCHA_PREFIX, FMC::KEY);
            $setting = JsonUtils::stringify($setting);
        } else {
            $setting = 'null';
        }

        $homeUrl = home_url();
        $urlPath = home_url('/bitform/', 'relative');
        $restUrl = get_rest_url() . 'bitform';
        $uploadUrl = wp_get_upload_dir()['baseurl'] . '/bitform';
        $nonce = wp_create_nonce('wp_rest');

        wp_enqueue_style('bitform', 'https://bitform.cdn.bitorre.net/20210610/app.css', array(), null);
        wp_enqueue_script('bitform', 'https://bitform.cdn.bitorre.net/20210610/app.js', array(), null, true);
        wp_add_inline_script('bitform', "
            window.FORM_MODEL = $formJson;
            window.FORM_SETTING = $setting;
            window.SERVER_URL = '$homeUrl';
            window.SERVER_API_URL = '$restUrl';
            window.SERVER_UPLOADS_URL = '$uploadUrl';
            window.CSRF_TOKEN = { \"X-WP-Nonce\": \"$nonce\" };
            window.routerBase = '$urlPath';
        ", 'before');

        if ($recaptchaV3) {
            wp_enqueue_script('recaptcha', "https://www.google.com/recaptcha/api.js?render=$recaptchaV3", array(), null, true);
        }

        $this->html('BitForm', '', array('bitform'), array('bitform', 'recaptcha'));
    }
    
    private function html($title, $content = '', $css = array(), $js = array())
    {
        header("HTTP/1.1 200 OK");
    ?>
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8" />
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
                <title>
                    <?php echo esc_html($title) ?>
                </title>
                <?php count($css) && wp_print_styles($css) ?>
            </head>
            <body>
                <div id="root">
                    <?php echo esc_html($content) ?>
                </div>
                <?php count($js) && wp_print_scripts($js) ?>
            </body>
        </html>
    <?php
        die();
    }

    private function error($message)
    {
        $this->html('Error', $message);
    }
}
