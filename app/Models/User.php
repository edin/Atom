<?php

namespace App\Models;

final class User
{
    public $id;
    public $username;
    public $email;

    public static function from($id, $username, $email)
    {
        $user = new static();
        $user->id = $id;
        $user->username = $username;
        $user->email = $email;
        return $user;
    }
}
