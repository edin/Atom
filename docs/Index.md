# Atom Framework Documentation

Atom is currently organized as one framework package plus a sample app.

The main concepts are:

- [Deployment](Deployment.md)
- [Application and Service Providers](ServiceProviders.md)
- [Pages and View Engine](PagesAndViews.md)
- [SIPA](SIPA.md)
- [Components](Components.md)
- [Router](Router.md)
- [Modules](Modules.md)
- [Paths](Paths.md)
- [Logging](Logging.md)
- [Identity and Authentication](Identity.md)
- [Accounts Module](Accounts.md)
- [Mail](Mail.md)
- [Background Jobs and Queues](Queue.md)
- [Task Scheduler](Scheduler.md)
- [Sessions](Sessions.md)
- [Cookies](Cookies.md)
- [CSRF Protection](Csrf.md)
- [Security Headers](SecurityHeaders.md)
- [Trusted Proxies](TrustedProxies.md)
- [Trusted Hosts](TrustedHosts.md)
- [CORS](Cors.md)
- [Request Middleware](RequestMiddleware.md)
- [Cache](Cache.md)
- [Rate Limiting](RateLimiting.md)
- [Error Pages](ErrorPages.md)
- [API Explorer](ApiExplorer.md)
- [Database](Database.md)
- [PostgreSQL Support Plan](PostgreSQLSupport.md)
- [Configuration](Configuration.md)
- [Dependency Injection](DependencyInjection.md)
- [Console](Console.md)
- [Publishing Files](Publishing.md)
- [Middlewares](Middlewares.md)
- [Validation](Validation.md)
- [Hydration](Hydration.md)

## Application Flow

At runtime the base application:

1. registers default path aliases and applies path overrides
2. loads environment files
3. creates typed config
4. registers framework and application service providers
5. creates the injector
6. configures the shared router
7. registers application job types
8. registers application scheduled tasks
9. registers application modules and their jobs and schedules
10. registers application components
11. registers application page directories
12. runs service provider bootstrappers
13. runs application bootstrap
14. dispatches the request through the middleware pipeline

The sample application's bootstrap is intentionally small:

```php
protected function bootstrap(Injector $injector): void
{
    Route::attach(ApiController::class);
}
```

Pages own browser-facing workflows. Controllers are still useful for APIs.

## Repository Layout

```text
.
├── src/
├── tests/
├── docs/
├── sample/
└── showcase/
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
