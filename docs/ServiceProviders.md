# Application and Service Providers

[Atom Framework](Index.md)

An Atom application extends `Atom\Application`.

Application setup is split into two hooks:

- `rootPath()` and `configurePaths()` control path aliases
- `environmentFiles()` customizes `.env` files loaded before config and services
- `services()` registers providers and bindings
- `modules()` registers framework and application modules
- `components()` registers application component tags
- `pages()` registers page directories
- `bootstrap()` performs runtime setup such as model database configuration and API routes

## Application Example

```php
namespace App;

use App\Components\Table;
use App\Controllers\ApiController;
use Atom\Database\DatabaseServices;
use Atom\Database\Db;
use Atom\Database\Model;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderRegistry;
use Atom\Module\ModuleRegistry;
use Atom\Modules\Framework\Framework;
use Atom\Page\PageRegistry;
use Atom\Router\Route;
use Atom\View\Component\ComponentRegistry;

final class Application extends \Atom\Application
{
    protected function rootPath(): string
    {
        return dirname(__DIR__);
    }

    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(DatabaseServices::fromConfig($this->getConfig(), $this->getPaths()));
    }

    protected function modules(ModuleRegistry $modules): void
    {
        $modules->add(Framework::module());
    }

    protected function pages(PageRegistry $pages): void
    {
        $pages->directory("@app/Pages");
    }

    protected function components(ComponentRegistry $components): void
    {
        $components->register("Table", Table::class);
    }

    protected function bootstrap(Injector $injector): void
    {
        Model::useDb($injector->get(Db::class));

        Route::attach(ApiController::class);
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
