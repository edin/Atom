# Paths

[Atom Framework](Index.md)

Atom includes a small path alias service for application paths.

The base application registers two aliases by default:

```text
@root  directory containing the concrete Application class
@app   @root/app
```

If your `Application` class lives inside `app/`, override the root path:

```php
protected function rootPath(): string
{
    return dirname(__DIR__);
}
```

Add or override aliases with `configurePaths()`:

```php
use Atom\Support\Paths;

protected function configurePaths(Paths $paths): void
{
    $paths->alias("storage", $this->path("@root/storage"));
}
```

Aliases use the `@name` form:

```php
$this->path("@root/.env");
$this->path("@app/Database/Migrations");
```

The same service can be injected:

```php
use Atom\Support\Paths;

final readonly class ArticleService
{
    public function __construct(private Paths $paths)
    {
    }
}
```

For paths that may already be absolute, use `resolveFrom()`:

```php
$database = $paths->resolveFrom("@root", "storage/app.sqlite");
```

`resolveFrom()` keeps absolute and already-aliased paths unchanged, otherwise it resolves them relative to the supplied base alias.
