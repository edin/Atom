<?php

declare(strict_types=1);

namespace Atom\Security;

use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;

final readonly class SecurityServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(CsrfTokenManagerInterface::class)
            ->to(CsrfTokenManager::class)
            ->scoped();

        $bindings->bind(CsrfMiddleware::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(SecurityHeadersMiddleware::class)
            ->toSelf()
            ->scoped();
    }
}
