<?php

namespace BitForm\Common;

use BitForm\Context;
use BitForm\Constant\FormMetaConstants as FMC;
use BitForm\Utils\JsonUtils;
use BitForm\Utils\StringUtils;

class Notification
{
    private $formRepository;
    private $formMetaRepository;

    public function __construct()
    {
        $this->formRepository = Context::$formRepository;
        $this->formMetaRepository = Context::$formMetaRepository;
    }

    public function send($formId, $entry)
    {
        $config = $this->formRepository->findConfigById($formId);
        $isOn = FMC::isSettingOn($config, FMC::SETTING_NOTIFICATION_PREFIX, FMC::ON_OFF);
        if (!$isOn) {
            return;
        }
        $settings = $this->formMetaRepository->findAllByFormIdAndScope($formId, FMC::SETTING_NOTIFICATION_PREFIX);
        $emailJson = FMC::getSetting($settings, FMC::SETTING_NOTIFICATION_PREFIX, FMC::EMAIL);
        if (!$emailJson) {
            return;
        }
        $emails = JsonUtils::parse($emailJson, true);
        $charset = apply_filters('wp_mail_charset', get_bloginfo('charset'));
        $adminEmail = get_bloginfo('admin_email');
        foreach ($emails as $email) {
            if (!$email['on']) {
                continue;
            }
            $recipients = $email['recipient'];
            $subject = $email['subject'];
            $content = $email['content'];
            if (!$recipients || !count($recipients) || !$subject || !$content) {
                continue;
            }
            $this->replaceEmail($recipients, $entry);
            $html = $this->html($content, $charset);
            $headers = [
                "Content-type: text/html; charset=$charset",
                "From: BitForm <$adminEmail>"
            ];
            $result = wp_mail($recipients, $subject, $html, $headers);
        }
    }

    private function replaceEmail(&$recipients, $entry)
    {
        foreach ($recipients as &$recipient) {
            if (!StringUtils::contains($recipient, '@')) {
                $recipient = $entry[$recipient];
            }
        }
    }

    private function html($content, $charset)
    {
        return
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta name="viewport" content="width=device-width" />
            <meta http-equiv="Content-Type" content="text/html; charset=' . esc_attr($charset) . '" />
        </head>
        <body style="margin:0;padding:0;">
            ' . $content . '
        </body>
        </html>';
    }
}
