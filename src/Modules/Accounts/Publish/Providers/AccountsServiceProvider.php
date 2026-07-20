<?php

declare(strict_types=1);

namespace App\Providers;

use App\Accounts\AccountManager;
use App\Identity\AppIdentityProvider;
use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;
use Atom\Identity\IdentityProviderInterface;
use Atom\Modules\Accounts\AccountManagerInterface;

final readonly class AccountsServiceProvider implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(IdentityProviderInterface::class)
            ->to(AppIdentityProvider::class)
            ->singleton();

        $bindings->bind(AccountManagerInterface::class)
            ->to(AccountManager::class)
            ->singleton();
    }
}
