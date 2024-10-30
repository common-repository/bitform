<?php

namespace BitForm\Utils;

class StringUtils
{

    public static function parseIds($ids)
    {
        $nums = array();
        if (!$ids) {
            return $nums;
        }
        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                continue;
            }
            $id = (int) $id;
            if ($id > 0) {
                $nums[] = $id;
            }
        }
        return array_unique($nums);
    }

    public static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return substr($haystack, 0, $length) === $needle;
    }

    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }

    public static function contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }

    public static function isJsonArray($str)
    {
        return self::startsWith($str, '[') && self::endsWith($str, ']');
    }
}
