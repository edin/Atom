<?php

declare(strict_types=1);

namespace Atom\Di;

interface ServiceProviderInterface
{
    public function register(Bindings $bindings): void;
}
