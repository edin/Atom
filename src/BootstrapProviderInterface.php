<?php

declare(strict_types=1);

namespace Atom;

interface BootstrapProviderInterface
{
    public function bootstrappers(Bootstrappers $bootstrappers): void;
}
