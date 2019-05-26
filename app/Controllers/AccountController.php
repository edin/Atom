<?php

namespace App\Controllers;

use Atom\View\ViewInfo;

final class AccountController
{
    final public function login()
    {
        return new ViewInfo('account/login');
    }

    final public function logout()
    {
        return new ViewInfo('account/logout');
    }
}
