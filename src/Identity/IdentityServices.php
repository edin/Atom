<?php

declare(strict_types=1);

namespace Atom\Identity;

use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;

final readonly class IdentityServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(PasswordHasherInterface::class)
            ->to(NativePasswordHasher::class)
            ->singleton();

        $bindings->bind(AuthenticatorInterface::class)
            ->to(SessionAuthenticator::class)
            ->scoped();

        $bindings->bind(AuthenticateMiddleware::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(GuestMiddleware::class)
            ->toSelf()
            ->scoped();
    }
}
