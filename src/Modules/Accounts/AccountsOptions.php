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
        public ?string $loginTemplate = null
    ) {
    }
}
