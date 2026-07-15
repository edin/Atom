<?php

declare(strict_types=1);

namespace Atom\Session;

use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;

final readonly class SessionServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(SessionInterface::class)
            ->to(NativeSession::class)
            ->scoped();

        $bindings->bind(FlashBag::class)
            ->toSelf()
            ->scoped();
    }
}
