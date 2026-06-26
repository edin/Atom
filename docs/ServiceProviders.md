# Service Providers

[Atom Framework](Index.md)

Service providers group DI registrations for the application.

```php
use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;

final class BlogServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(PostRepository::class)
            ->toSelf()
            ->singleton();
    }
}
```

## Registering Providers

Applications register providers in `services()`:

```php
use Atom\Di\ServiceProviderRegistry;

final class Application extends \Atom\Application
{
    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(BlogServices::class);
    }
}
```

Provider instances can be registered too:

```php
use Atom\Database\DatabaseServices;
use Atom\Database\Driver\MySqlDriver;
use Atom\Di\ServiceProviderRegistry;

protected function services(ServiceProviderRegistry $providers): void
{
    $providers->add(new DatabaseServices(
        new MySqlDriver(database: "app", username: "root", password: "root")
    ));
}
```

## Bootstrap

Runtime configuration belongs in the application `bootstrap()` hook. This is where routes and other runtime setup are usually configured.

```php
use Atom\Di\Injector;
use Atom\Router\Route;

final class Application extends \Atom\Application
{
    protected function bootstrap(Injector $injector): void
    {
        Route::get("/", [HomeController::class, "index"]);
        Route::get("/about", [HomeController::class, "about"]);
    }
}
```

The base application registers framework defaults before application services:

- dispatcher services
- view services
- shared router
- request and response bindings

