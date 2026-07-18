<?php

declare(strict_types=1);

namespace Atom\Publish;

use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;

final readonly class PublishServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(Publisher::class)
            ->toSelf()
            ->singleton();
    }
}
