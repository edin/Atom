<?php

declare(strict_types=1);

namespace App;

use App\Components\Table;
use App\Controllers\ApiController;
use Atom\Database\DatabaseServices;
use Atom\Database\Db;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Model;
use Atom\Database\Migration\MigrationOptions;
use Atom\Database\Seeder\SeederOptions;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderRegistry;
use Atom\Page\Page;
use Atom\Router\Route;
use Atom\View\Component\ComponentRegistry;

final class Application extends \Atom\Application
{
    protected string $baseUrl = "";

    protected function services(ServiceProviderRegistry $providers): void
    {
        $storage = dirname(__DIR__) . "/storage";
        if (!is_dir($storage)) {
            mkdir($storage, 0777, true);
        }

        $providers->add(new DatabaseServices(
            new SqliteDriver($storage . "/atom_sample.sqlite"),
            new MigrationOptions(dirname(__DIR__) . "/app/Database/Migrations"),
            new SeederOptions(dirname(__DIR__) . "/app/Database/Seeders")
        ));
    }

    protected function bootstrap(Injector $injector): void
    {
        Model::useDb($injector->get(Db::class));

        $injector->get(ComponentRegistry::class)
            ->register("Table", Table::class);

        Route::attach(ApiController::class);

        Page::registerPages();
    }
}
