# Atom Framework Documentation

Atom is currently organized as one framework package plus a sample app.

The main concepts are:

- [Application and Service Providers](ServiceProviders.md)
- [Pages and View Engine](PagesAndViews.md)
- [Components](Components.md)
- [Router](Router.md)
- [Modules](Modules.md)
- [API Explorer](ApiExplorer.md)
- [Database](Database.md)
- [PostgreSQL Support Plan](PostgreSQLSupport.md)
- [Configuration](Configuration.md)
- [Dependency Injection](DependencyInjection.md)
- [Console](Console.md)
- [Middlewares](Middlewares.md)
- [Validation](Validation.md)

## Application Flow

At runtime the base application:

1. registers framework service providers
2. lets the application register its services
3. creates the injector
4. configures the shared router
5. runs application bootstrap
6. dispatches the request through the middleware pipeline

The sample application's bootstrap is intentionally small:

```php
protected function bootstrap(Injector $injector): void
{
    Model::useDb($injector->get(Db::class));

    Route::attach(ApiController::class);

    Page::registerPages();
}
```

Pages own browser-facing workflows. Controllers are still useful for APIs.

## Repository Layout

```text
.
├── src/
├── tests/
├── docs/
└── sample/
```

The sample app depends on the local framework package through:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": ".."
    }
  ]
}
```

This keeps framework and sample development together while still leaving room to split packages later.

Local sample configuration can live in `sample/.env`:

```env
APP_ENV=local
APP_DEBUG=true
DB_DRIVER=sqlite
DB_DATABASE=storage/atom_sample.sqlite
```
