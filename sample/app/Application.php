<?php

declare(strict_types=1);

namespace App;

use App\Components\ConfirmDialog;
use App\Components\Table;
use Atom\Config\Env;
use App\Controllers\ApiController;
use Atom\Modules\ApiExplorer\ApiExplorer;
use Atom\Database\DatabaseConfig;
use Atom\Database\DatabaseServices;
use Atom\Database\DatabaseDriverFactory;
use Atom\Database\Db;
use Atom\Database\Model;
use Atom\Database\Migration\MigrationOptions;
use Atom\Database\Seeder\SeederOptions;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderRegistry;
use Atom\Modules\Framework\Framework;
use Atom\Page\Page;
use Atom\Router\Route;
use Atom\View\Component\ComponentRegistry;

final class Application extends \Atom\Application
{
    protected string $baseUrl = "";

    protected function services(ServiceProviderRegistry $providers): void
    {
        $root = dirname(__DIR__);
        Env::loadIfExists($root . "/.env");

        $database = DatabaseConfig::fromEnv();
        if (strtolower($database->driver) === "sqlite") {
            $storage = dirname($this->path($root, $database->database));
            if (!is_dir($storage)) {
                mkdir($storage, 0777, true);
            }
        }

        $providers->add(new DatabaseServices(
            (new DatabaseDriverFactory($root))->create($database),
            new MigrationOptions($root . "/app/Database/Migrations"),
            new SeederOptions($root . "/app/Database/Seeders")
        ));
    }

    protected function bootstrap(Injector $injector): void
    {
        Model::useDb($injector->get(Db::class));

        $injector->get(ComponentRegistry::class)
            ->register("Table", Table::class)
            ->register("ConfirmDialog", ConfirmDialog::class);

        Route::attach(ApiController::class);
        $this->registerModule(Framework::module());
        $this->registerModule(ApiExplorer::module("/atom/api"));

        Page::registerPages();
    }

    private function path(string $root, string $path): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\/\\\\]|[\/\\\\])/', $path) === 1) {
            return $path;
        }

        return $root . "/" . ltrim($path, "/\\");
    }
}
