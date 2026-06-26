# Application and Service Providers

[Atom Framework](Index.md)

An Atom application extends `Atom\Application`.

Application setup is split into two hooks:

- `services()` registers providers and bindings
- `bootstrap()` performs runtime setup such as model database configuration, API routes, and page discovery

## Application Example

```php
namespace App;

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

final class Application extends \Atom\Application
{
    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(new DatabaseServices(
            new SqliteDriver(__DIR__ . "/../storage/app.sqlite"),
            new MigrationOptions(__DIR__ . "/Database/Migrations"),
            new SeederOptions(__DIR__ . "/Database/Seeders")
        ));
    }

    protected function bootstrap(Injector $injector): void
    {
        Model::useDb($injector->get(Db::class));

        Route::attach(ApiController::class);

        Page::registerPages();
    }
}
```

## Framework Defaults

The base application registers framework services for:

- console
- dispatcher
- pages and view rendering
- legacy PHP views
- router
- request and response wrappers

Application providers are added on top of those defaults.

## Service Providers

Service providers group DI registrations:

```php
use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;

final class BlogServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(PostService::class)
            ->toSelf()
            ->singleton();
    }
}
```

Register a provider class:

```php
$providers->add(BlogServices::class);
```

Register a configured provider instance:

```php
$providers->add(new DatabaseServices(new SqliteDriver($path)));
```

## Console Providers

Providers can also expose console command discovery paths by implementing `ConsoleCommandProviderInterface`:

```php
use Atom\Console\ConsoleCommandProviderInterface;
use Atom\Console\ConsoleCommandSources;
use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;

final class BlogServices implements ServiceProviderInterface, ConsoleCommandProviderInterface
{
    public function register(Bindings $bindings): void
    {
    }

    public function consoleCommands(ConsoleCommandSources $commands): void
    {
        $commands->add(__DIR__ . "/Commands", "App\\Blog\\Commands");
    }
}
```

The application console discovers these command sources automatically.
