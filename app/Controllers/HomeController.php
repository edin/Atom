<?php

namespace App\Controllers;

// use Opis\Database\Database;
// use Opis\Database\Connection;

use Atom\ViewInfo;

class HomeController extends Controller
{
    public function index() {
        // ---------------------------------- ---------------------------------- ---------------------------------- //
        // $connection = new Connection('mysql:host=localhost;dbname=chatbot', 'root', 'root');
        // $db         = new Database($connection);
        // $users      = $db->from("users")->select()->all();
        // ---------------------------------- ---------------------------------- ---------------------------------- //
        // throw new \Exception("Hello");

        $users = [];
        for($i = 0; $i < 10; $i ++) {
            $user = new \stdClass;
            $user->id       = "ID $i";
            $user->uid      = "UID $i";
            $user->username = "Username $i";
            $user->email    = "Email $i";
            $users[] = $user;
        }

        return new ViewInfo('home/index', [
            'items' => $users
        ]);
    }
}