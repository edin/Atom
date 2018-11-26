<?php

namespace App\Controllers;

use Atom\ViewInfo;

class AccountController extends Controller
{
    public function login() {
        return new ViewInfo('account/login');
    }

    public function logout() {
        return new ViewInfo('account/logout');
    }
}