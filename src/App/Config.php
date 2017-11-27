<?php

namespace App;

class Config
{
  private static $conf;

  public static function load() {
    self::$conf = require(ROOT_DIR.'/config.php');
  }

  public static function get($key) {
    return self::$conf[$key];
  }
}