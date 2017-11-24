<?php

namespace App;

class User {
    private $file = ROOT_DIR.'/db/users.json';

    public function __construct()
    {
        if (!file_exists($this->file)) {
            copy($this->file.'.dist', $this->file);
        }
    }

    public function isValid(string $email, string $password) {
        $users = json_decode(file_get_contents($this->file), true);
        $myusers = array_filter($users, function($n) use ($email, $password) {
            return $n['email'] == $email && $n['password'] == $password;
        });
        return count($myusers) == 1 ? $myusers[0] : false;
    }

    public function getBySession(string $session) {
        $users = json_decode(file_get_contents($this->file), true);
        $myusers = array_filter($users, function($n) use ($session) {
            return md5($n['email']) == $session;
        });
        return count($myusers) == 1 ? $myusers[0] : false;
    }
}
