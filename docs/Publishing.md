# Publishing Files

[Atom Framework](Index.md)

Publish bundles describe framework or module files that an application can copy and own. The publisher is independent of the console, so module commands remain small and publishing behavior stays consistent.

## Define a Bundle

```php
use Atom\Publish\PublishBundle;

$bundle = (new PublishBundle(
    "accounts",
    sourceDirectory: __DIR__ . "/Publish"
))
    ->file(
        "Models/User.php",
        "@app/Models/User.php"
    )
    ->file(
        "Migrations/M0001_create_users.php",
        "@migrations/M0001_create_users.php"
    );
```

Relative source paths are resolved from the bundle's `sourceDirectory`. Without a source directory, they are resolved from `@root`. Absolute and aliased source paths bypass the bundle directory. Destinations may be absolute, root-relative, or use registered path aliases.

Bundles use explicit files rather than copying an entire directory. This makes the public scaffolding contract visible and prevents an unrelated internal file from being published accidentally.

## Publish a Bundle

```php
use Atom\Publish\Publisher;

$result = $publisher->publish($bundle);
```

Existing files are skipped by default:

```php
$result = $publisher->publish($bundle, force: true);
```

The result separates paths by outcome:

```php
$result->published;
$result->skipped;
$result->overwritten;
$result->changed();
```

Before writing, the publisher validates every source, rejects duplicate resolved destinations, and rejects destinations occupied by directories. Parent directories are created when needed.

## Thin Module Command

An installed module can register its command directory with `ModuleContext::commands()`. The command only defines its bundle, invokes the framework publisher, and formats the result:

```php
final readonly class AccountsCommands
{
    public function __construct(
        private AccountsPublishBundle $bundle,
        private Publisher $publisher,
        private ConsoleOutput $output
    ) {
    }

    #[ConsoleCommand("accounts:publish", "Publish account scaffolding")]
    public function publish(bool $force = false): int
    {
        $result = $this->publisher->publish($this->bundle->bundle(), $force);

        foreach ($result->published as $file) {
            $this->output->line("Published: " . $this->output->command($file));
        }
        foreach ($result->overwritten as $file) {
            $this->output->line("Overwritten: " . $this->output->command($file));
        }
        foreach ($result->skipped as $file) {
            $this->output->line("Skipped: " . $this->output->muted($file));
        }

        return 0;
    }
}
```

Published files are application-owned. The framework does not track or update them after publication.
