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
      ->setBody($this->formatBody($email, $order), 'text/html');
    $failures = [];
    $this->mailer->send($message, $failures);
    Log::mail($this->receiverEmail, $subject, $failures);
    return empty($failures);
  }

  private function formatBody($email, $order)
  {
    $order = $order['broetchen'];
    $gesamtkosten = 0.0;
    $liste = array_map(function($name) use ($order, &$gesamtkosten) {
        if ($name == 'gesamt') return '';
        $gesamtkosten+= floatval($order[$name]['kosten']);
        return "<tr><td>".$name."</td><td>".$order[$name]['anzahl']." Stück</td><td>".number_format($order[$name]['kosten'], 2, ',', '')." €</td></tr>";
    }, array_keys($order));
    return 'Hier ist die Bestellung von '.$email.': '."\n<br/><br/>"
        ."\n<table>".implode("\n", $liste)."</table>"
        ."\n\n<br/><br/>Gesamtkosten: ".number_format($gesamtkosten, 2, ',', '')." €";
  }
}