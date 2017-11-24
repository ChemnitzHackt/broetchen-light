<?php

namespace App;

class Log
{
  private static $file;

  public static function start() {
    self::$file = ROOT_DIR.'/log/'.date('Y-m').'.log';
  }

  private static function log($msg) {
    $row = date('Y-m-d H:i:s').' - '.$msg;
    file_put_contents(self::$file, "\n".$row, FILE_APPEND);
  }

  public static function login($email, $success)
  {
    self::log('Login '.($success?'erfolgreich':'fehlerhaft').' als '.$email);
  }

  public static function order($email, $order)
  {
    self::log('Bestellung als '.$email.' : '.json_encode($order));
  }

  public static function mail($receiver, $subject, $failures)
  {
    self::log('Mail geschickt an '.$receiver.' - Betreff: '.$subject.(!empty($failures) ? ' - Fehler: '.json_encode($failures) : ''));
  }

}