<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Config\Options;

#[Options("ACCOUNTS_")]
final readonly class AccountsOptions
{
    public function __construct(
        public string $title = "Sign in",
        public string $afterLogin = "/",
        public string $afterLogout = "/",
        public int $loginMaxAttempts = 5,
        public int $loginWindowSeconds = 60,
        public int $registerMaxAttempts = 5,
        public int $registerWindowSeconds = 60,
        public int $forgotPasswordMaxAttempts = 3,
        public int $forgotPasswordWindowSeconds = 60,
        public int $resetPasswordMaxAttempts = 5,
        public int $resetPasswordWindowSeconds = 60,
        public ?string $loginTemplate = null,
        public ?string $registerTemplate = null,
        public ?string $forgotPasswordTemplate = null,
        public ?string $resetPasswordTemplate = null
    ) {
    }
}
