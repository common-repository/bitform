<?php

namespace BitForm\Utils;

use BitForm\Exception\JsonException;

class JsonUtils
{

  public static function parse($json, $asArray = false)
  {
    $result = json_decode($json, $asArray);
    $error = json_last_error();
    if ($error !== JSON_ERROR_NONE) {
      throw new JsonException($error);
    }
    return $result;
  }

  public static function stringify($obj)
  {
    $result = json_encode($obj);
    $error = json_last_error();
    if ($error !== JSON_ERROR_NONE) {
      throw new JsonException($error);
    }
    return $result;
  }
}
