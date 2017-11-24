<?php

namespace App;

class Order
{
  public function saveOrder($email, $order)
  {
    $json = ROOT_DIR.'/db/orders-'.$email.'.json';
    file_put_contents($json, json_encode($order, JSON_PRETTY_PRINT));
    Log::order($email, $order);
  }
}