# Console

[Atom Framework](Index.md)

Atom console commands are small classes resolved through the DI container.

## Command Class

The easiest way to write a command is to extend `Command` and implement `execute()`:

```php
use Atom\Console\Command;
use Atom\Console\ConsoleOutput;

final class GreetCommand extends Command
{
    protected static string $name = "greet";
    protected static string $description = "Greets a user";

    public function __construct(
        private GreetingService $greeting,
        private ConsoleOutput $output
    ) {
    }

    protected function execute(string $name = "World", bool $loud = false): int
    {
        $message = $this->greeting->greet($name);

        if ($loud) {
            $message = strtoupper($message);
        }

        $this->output->line($message);

        return 0;
    }
}
```

The base class provides:

```php
$this->argument(0);
$this->arguments();
$this->option("name");
$this->hasOption("force");
$this->write("Loading...");
$this->line("done");
$this->errorLine("Something failed");
```

`execute()` can also receive bound parameters:

```php
protected function execute(string $name, string $role = "user", bool $force = false): int
{
    // php atom user:create Edin --role=admin --force
}
```

Binding rules are the same as command groups:

- positional arguments bind to scalar parameters in method order
- `--name=value` binds to a parameter with the same name
- `--force` binds to `bool $force`
- class-typed parameters are resolved from DI
- `ConsoleInput` and `ConsoleOutput` can be injected through the constructor or requested directly
- returning `string` writes to output
- returning `int` sets the exit code

For lower-level control, implement `CommandInterface` directly:

```php
use Atom\Console\CommandInterface;
use Atom\Console\ConsoleInput;
use Atom\Console\ConsoleOutput;

final readonly class GreetCommand implements CommandInterface
{
    public function __construct(private GreetingService $greeting)
    {
    }

    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $name = $input->argument(0, "World");

        $output->line($this->greeting->greet($name));

        return 0;
    }
}
```

## Register Commands

```php
use Atom\Console\ConsoleApplication;

$console = new ConsoleApplication($injector);

$console->add(GreetCommand::class);
```

Command classes are resolved through the injector, so constructor dependencies work normally.

You can also register a class with an explicit name:

```php
$console->command("greet", GreetCommand::class, "Greets a user");
```

Command instances can also be registered:

```php
$console->command("ping", new PingCommand());
```

## Command Groups

Small related commands can be grouped into one class with method attributes:

```php
use Atom\Console\Attributes\ConsoleCommand;
use Atom\Console\ConsoleOutput;

final readonly class UserCommands
{
    public function __construct(
        private UserRepository $users,
        private ConsoleOutput $output
    ) {
    }

    #[ConsoleCommand("user:create", "Creates a user")]
    public function create(string $name, string $role = "user", bool $force = false): int
    {
        $this->users->create($name, $role, $force);

        return 0;
    }

    #[ConsoleCommand("user:greet", "Greets a user")]
    public function greet(string $name): string
    {
        return "Hello, {$name}!" . PHP_EOL;
    }

    #[ConsoleCommand("user:count", "Counts users")]
    public function count(): int
    {
        $this->output->line((string) $this->users->count());

        return 0;
    }
}
```

Attach the group:

```php
$console->attach(UserCommands::class);
```

Method parameters are bound from CLI arguments and options:

```text
php atom user:create Edin --role=admin --force
```

Binding rules:

- positional arguments bind to scalar parameters in method order
- `--name=value` binds to a parameter with the same name
- `--force` binds to `bool $force`
- class-typed parameters are resolved from DI
- `ConsoleInput` and `ConsoleOutput` are command-scoped services and can be constructor-injected
- returning `string` writes to output
- returning `int` sets the exit code

## Discovery

Commands can be discovered from a PSR-4 style folder:

```php
$console = new ConsoleApplication($injector);

$console->discover(__DIR__ . "/Commands", "App\\Commands");

exit($console->run($argv));
```

This discovers both command classes:

```php
namespace App\Commands;

use Atom\Console\Command;

final class GreetCommand extends Command
{
    protected static string $name = "greet";
    protected static string $description = "Greets a user";

    protected function execute(string $name = "World"): string
    {
        return "Hello, {$name}!" . PHP_EOL;
    }
}
```

And command groups:

```php
namespace App\Commands;

use Atom\Console\Attributes\ConsoleCommand;

final readonly class UserCommands
{
    #[ConsoleCommand("user:create", "Creates a user")]
    public function create(string $name): int
    {
        return 0;
    }
}
```

Discovered commands appear in `help`:

```text
php atom help
```

## Application Console

Applications can use the framework-provided console application:

```php
$app = new App\Application();
$app->initialize();

exit($app->getConsole()->run($argv));
```

Framework services can also provide command discovery paths. A service provider may implement `ConsoleCommandProviderInterface`:

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

When the application resolves `ConsoleApplication`, commands from all providers are discovered automatically. Atom also exposes built-in framework commands this way.

Installed modules can contribute command directories directly from their `register()` method:

```php
public function register(ModuleContext $context): void
{
    $context->commands(
        __DIR__ . "/Commands",
        __NAMESPACE__ . "\\Commands"
    );
}
```

This uses the same command discovery mechanism as console command providers and keeps optional module commands tied to module installation.

## Run

```php
exit($console->run($argv));
```

Arguments and options are available through `ConsoleInput`:

```php
$input->commandName();
$input->argument(0);
$input->arguments();
$input->option("name");
$input->hasOption("force");
```

Supported option forms:

```text
atom greet Edin --loud --role=admin -v
```

## Output

Use `ConsoleOutput` for command output:

```php
$output->write("Loading...");
$output->line("done");
$output->errorLine("Something failed");
```

Tests can use `BufferedConsoleOutput`.

## File Templates

Make-style commands can render framework templates through `FileTemplateRenderer`.

```php
use Atom\Console\FileTemplateRenderer;

$contents = $templates->render("database/migration.php.tpl");
```

Templates live under:

```text
src/Templates/
```

Applications can override them by placing files with the same relative path under:

```text
templates/atom/
```

For example, this app template replaces the framework page class template:

```text
templates/atom/page/page.php.tpl
```

The renderer supports simple placeholders:

```text
Hello {{ name }}
```

```php
$templates->render("hello.tpl", ["name" => "Atom"]);
```

Current built-in templates are used by:

- `make:migration`
- `make:seeder`
- `make:page`
- `make:component`

## Make Commands

Atom ships with a few project generators:

```text
php atom make:page Articles
php atom make:page "Admin Users" /admin/users
php atom make:component AlertBox
```

`make:page` creates:

```text
app/Pages/ArticlesPage.php
app/Pages/ArticlesPage.atom.html
```

The generated page uses `#[PageRoute]` and extends `Atom\Page\Page`.

`make:component` creates:

```text
app/Components/AlertBox.php
```

Generated files are intentionally small. Customize them by copying the matching framework template into `templates/atom` and editing the copy.

## Help

`help`, `--help`, and `-h` render the registered command list.
