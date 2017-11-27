<?php

namespace App;

class Mailer
{
  private $mailer;
  private $senderEmail;
  private $receiverEmail;

  public function __construct()
  {
    $this->senderEmail = Config::get('sender_email');
    $this->receiverEmail = Config::get('receiver_email');

    $transport = (new \Swift_SmtpTransport(Config::get('smtp_server'), Config::get('smtp_port'), 'ssl'))
      ->setUsername(Config::get('smtp_user'))
      ->setPassword(Config::get('smtp_password'))
      ->setStreamOptions(['ssl' => ['allow_self_signed' => true, 'verify_peer' => false]]);
    $this->mailer = new \Swift_Mailer($transport);
  }

  public function sendOrder($email, $order)
  {
    $subject = 'Brötchen Bestellung von '.$email;
    $message = (new \Swift_Message($subject))
      ->setFrom([$this->senderEmail => 'Brötchen Mailer'])
      ->setTo([$this->receiverEmail])
      ->setBody('Hier ist die Bestellung von '.$email.': '."\n".print_r($order,1));
    $failures = [];
    $this->mailer->send($message, $failures);
    Log::mail($this->receiverEmail, $subject, $failures);
    return empty($failures);
  }
}