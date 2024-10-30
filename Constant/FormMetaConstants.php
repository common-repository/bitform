<?php

namespace BitForm\Constant;

class FormMetaConstants
{
    const ON_OFF = 'onOff';
    const START_END_DATE = 'startEndDate';
    const START_END_TIME = 'startEndTime';
    const MAX = 'max';

    const MODE = 'mode';
    const URL = 'url';
    const HTML = 'html';

    const KEY = 'key';
    const SECRET  = 'secret';

    const EMAIL = 'email';
    const SUBJECT = 'subject';
    const RECIPIENT = 'recipient';
    const CONTENT = 'content';

    const SETTING_BASE_PREFIX = 'setting.base';
    const SETTING_SUBMIT_PREFIX = 'setting.submit';
    const SETTING_CAPTCHA_PREFIX = 'setting.captcha';
    const SETTING_NOTIFICATION_PREFIX = 'setting.notification';

    const PREFIXES = [
        self::SETTING_BASE_PREFIX => [[
            'name' => self::ON_OFF,
            'index' => 0
        ], [
            'name' => self::START_END_DATE,
            'index' => 1
        ], [
            'name' => self::START_END_TIME,
            'index' => 2
        ], [
            'name' => self::MAX,
            'index' => 3
        ]],
        self::SETTING_SUBMIT_PREFIX => [[
            'name' => self::MODE,
            'index' => 4
        ], [
            'name' => self::URL
        ], [
            'name' => self::HTML
        ]],
        self::SETTING_CAPTCHA_PREFIX => [[
            'name' => self::ON_OFF,
            'index' => 5
        ], [
            'name' => self::KEY
        ], [
            'name' => self::SECRET
        ]],
        self::SETTING_NOTIFICATION_PREFIX => [[
            'name' => self::ON_OFF,
            'index' => 6
        ], [
            'name' => self::EMAIL
        ]]
    ];

    public static function parseConfig($config, $meta)
    {
        $configArr = [];
        foreach ($meta as $v) {
            $index = $v['index'];
            if ($index === null) continue;
            $configArr[$v['name']] = ($config >> $index) & 1;
        }
        return $configArr;
    }

    public static function updateConfig($config, $meta, $data)
    {
        foreach ($meta as $v) {
            $index = $v['index'];
            if ($index === null) continue;
            $name = $v['name'];
            $value = $data[$name];
            if ($value) {
                $config |= (1 << $index);
            } else {
                $config &= ~(1 << $index);
            }
        }
        return $config;
    }

    public static function isSettingOn($config, $prefix, $name)
    {
        $settings = self::PREFIXES[$prefix];
        foreach ($settings as $setting) {
            if ($setting['name'] === $name) {
                $index = $setting['index'];
                return (bool) (($config >> $index) & 1);
            }
        }
        return false;
    }

    public static function getSetting($settings, $prefix, $name)
    {
        return $settings[$prefix . '.' . $name];
    }

    public static function setSetting(&$target, $settings, $prefix, $name)
    {
        $fullname = $prefix . '.' . $name;
        $target[$fullname] = $settings[$fullname];
    }

    public static function isBaseSettingOn($config, $name)
    {
        return self::isSettingOn($config, self::SETTING_BASE_PREFIX, $name);
    }

    public static function getBaseSetting($settings, $name)
    {
        return self::getSetting($settings, self::SETTING_BASE_PREFIX, $name);
    }
}
