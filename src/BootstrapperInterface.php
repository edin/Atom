<?php

declare(strict_types=1);

namespace Atom;

use Atom\Di\Injector;

interface BootstrapperInterface
{
    public function bootstrap(Injector $injector): void;
}
