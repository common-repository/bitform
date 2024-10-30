<?php

namespace BitForm\Utils;

class DateTimeUtils
{

  public static function getDate($offset = 0)
  {
    $datetime = new \DateTime('now', wp_timezone());
    if ($offset != 0) {
      $datetime->add(new \DateInterval('P' . $offset . 'D'));
    }
    return $datetime->format('Y-m-d');
  }

  public static function currentDateTime()
  {
    return current_time('mysql');
  }

  public static function getStartEndDateTime($range = '')
  {
    $timezone = wp_timezone();
    $now = new \DateTime('now', $timezone);
    $tomorrow = new \DateTime('tomorrow', $timezone);
    switch ($range) {
      case 'day': {
          return [$now->format('Y-m-d') . ' 00:00:00', $tomorrow->format('Y-m-d') . ' 00:00:00'];
        }
      case 'week': {
          $week = new \DateTime('this week', $timezone);
          return [$week->format('Y-m-d') . ' 00:00:00', $tomorrow->format('Y-m-d') . ' 00:00:00'];
        }
      case 'month': {
          return [$now->format('Y-m-01') . ' 00:00:00', $tomorrow->format('Y-m-d') . ' 00:00:00'];
        }
      case 'year': {
          return [$now->format('Y-01-01') . ' 00:00:00', $tomorrow->format('Y-m-d') . ' 00:00:00'];
        }
      default:
        return [null, null];
    }
  }
}
