<?php

declare(strict_types=1);

namespace Atom;

interface ApplicationBootstrapperProviderInterface
{
    public function bootstrappers(ApplicationBootstrappers $bootstrappers): void;
}
