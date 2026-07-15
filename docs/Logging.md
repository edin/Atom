# Logging

[Atom Framework](Index.md)

Atom logging starts with a small typed service:

```php
use Atom\Logging\LoggerInterface;

final readonly class ArticleService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function publish(int $id): void
    {
        $this->logger->info("Article published", ["id" => $id]);
    }
}
```

Register file logging as a module:

```php
use Atom\Config\Config;
use Atom\Module\ModuleRegistry;
use Atom\Modules\Logging\Logging;
use Atom\Modules\Logging\LoggingOptions;

protected function configure(Config $config): void
{
    $config->set(new LoggingOptions(
        path: dirname(__DIR__) . "/storage/logs/app.log"
    ));
}

protected function modules(ModuleRegistry $modules): void
{
    $modules->add(Logging::module());
}
```

The options can also be hydrated from environment values:

```env
LOG_PATH=storage/logs/app.log
```

For quick setup, `Logging::file()` is still available:

```php
$modules->add(Logging::file(__DIR__ . "/../storage/logs/app.log"));
```

The logger writes one line per event:

```text
[2026-06-29 12:00:00] INFO: Article published {"id":42}
```

## Facade

For places where constructor injection is awkward, use the thin facade:

```php
use Atom\Logging\Log;

Log::info("Article published", ["id" => 42]);
Log::error("Publish failed", ["exception" => $exception]);
```

The facade resolves `LoggerInterface` from `Container::get(LoggerInterface::class)`. If no application or logger is configured yet, it falls back to `NullLogger`.

## Container Bridge

`Container` is the official static bridge to the current application injector:

```php
use Atom\Container;
use Atom\Logging\LoggerInterface;

$logger = Container::get(LoggerInterface::class);
```

Prefer constructor injection for application code. Use `Container` and `Log` for bootstrapping, scripts, framework internals, or short-lived glue code.
