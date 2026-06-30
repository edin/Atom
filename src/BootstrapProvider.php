<?php

declare(strict_types=1);

namespace Atom;

interface BootstrapProvider
{
    public function bootstrappers(Bootstrappers $bootstrappers): void;
}
