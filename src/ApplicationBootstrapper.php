<?php

declare(strict_types=1);

namespace Atom;

use Atom\Di\Injector;

interface ApplicationBootstrapper
{
    public function bootstrap(Injector $injector): void;
}
