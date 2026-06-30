<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Bootstrapper;
use Atom\Di\Injector;

final readonly class ModelDatabaseBootstrapper implements Bootstrapper
{
    public function bootstrap(Injector $injector): void
    {
        Model::useDb($injector->get(Db::class));
    }
}
