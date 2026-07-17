<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

final readonly class Accounts
{
    public static function module(?AccountsOptions $options = null): AccountsModule
    {
        return new AccountsModule($options);
    }
}
