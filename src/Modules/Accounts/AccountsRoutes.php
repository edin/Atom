<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

final readonly class AccountsRoutes
{
    public function __construct(
        public string $login,
        public string $logout,
        public string $stylesheet
    ) {
    }
}
