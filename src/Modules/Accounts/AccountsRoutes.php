<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

final readonly class AccountsRoutes
{
    public function __construct(
        public string $login,
        public string $logout,
        public string $register,
        public string $forgotPassword,
        public string $resetPassword,
        public string $stylesheet
    ) {
    }
}
