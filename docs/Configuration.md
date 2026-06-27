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
