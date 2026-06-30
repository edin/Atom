# Configuration

[Atom Framework](Index.md)

Atom includes a small `.env` loader for application configuration.

It is intentionally limited and dependency-free. It supports simple key/value files:

```env
APP_ENV=local
APP_DEBUG=true
DB_DRIVER=sqlite
DB_DATABASE=storage/atom_sample.sqlite
DB_HOST=localhost
DB_PORT=
DB_USERNAME=
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

## Loading

Applications load `@root/.env` by default. Environment files are loaded before typed config is created and before services are registered.

Override `environmentFiles()` to use custom or multiple files:

```php
protected function environmentFiles(): array
{
    return [
        "@root/.env",
        "@root/.env.local",
    ];
}
```

Load a file when it exists:

```php
use Atom\Config\Env;

Env::loadIfExists(dirname(__DIR__) . "/.env");
```

Load a required file:

```php
Env::load(dirname(__DIR__) . "/.env");
```

Existing environment variables are not overwritten by default.

```php
Env::load($path, override: true);
```

## Reading Values

```php
Env::string("APP_ENV", "production");
Env::bool("APP_DEBUG", false);
Env::int("HTTP_PORT", 8080);
Env::float("RATIO", 1.0);
Env::get("OPTIONAL_VALUE");
Env::has("DB_DATABASE");
```

Boolean values recognize:

```text
true:  1, true, yes, on
false: 0, false, no, off, empty string
```

## Syntax

Supported syntax:

```env
# comments
NAME=Atom
EMPTY=
QUOTED="Hello Atom"
export EXPORTED=yes
```

The loader does not currently support advanced dotenv features like variable expansion or multiline values.

## Typed Options

`Config` stores typed options objects and can hydrate them from environment values.

```php
use Atom\Config\Config;
use Atom\Config\Options;

#[Options(prefix: "LOG_")]
final readonly class LoggingOptions
{
    public function __construct(
        public string $path = "storage/logs/app.log"
    ) {
    }
}

$config = Config::fromEnv();
$options = $config->options(LoggingOptions::class);
```

This reads:

```env
LOG_PATH=storage/logs/app.log
```

Options are constructor-based, so readonly classes work naturally. Missing environment values use constructor defaults. Required constructor parameters without defaults must be present in the environment.

Use `FromEnv` when a parameter should read a custom key:

```php
use Atom\Config\FromEnv;

#[Options(prefix: "LOG_")]
final readonly class LoggingOptions
{
    public function __construct(
        #[FromEnv("FILE")]
        public string $path = "storage/logs/app.log"
    ) {
    }
}
```

This reads `LOG_FILE`.

Supported conversions:

- `string`
- `int`
- `float`
- `bool`
- nullable values
- backed enums

Applications receive one shared config registry:

```php
protected function configure(Config $config): void
{
    $config->set(new LoggingOptions(
        path: dirname(__DIR__) . "/storage/logs/app.log"
    ));
}
```

`configure()` runs after environment files are loaded. `services()`, `bootstrap()`, and modules all receive the same config instance.

Modules can read options from their module context:

```php
$options = $context->config->options(LoggingOptions::class);
```

Options classes marked with `#[Options]` can also be injected directly through the application container:

```php
final readonly class ArticleController
{
    public function __construct(private LoggingOptions $logging)
    {
    }
}
```

An explicit binding for the same class still wins when one is registered.
