<?php

declare(strict_types=1);

namespace Atom;

use Atom\Di\Injector;

interface Bootstrapper
{
    public function bootstrap(Injector $injector): void;
}
