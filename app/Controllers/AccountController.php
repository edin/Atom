<?php

namespace App\Controllers;

use Atom\ViewInfo;

final class AccountController extends Controller
{
    public final function login() {
        return new ViewInfo('account/login');
    }

    public final function logout() {
        return new ViewInfo('account/logout');
    }
}