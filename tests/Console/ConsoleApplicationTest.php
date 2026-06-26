<?php

declare(strict_types=1);

namespace Atom\Tests\Console;

use Atom\Console\BufferedConsoleOutput;
use Atom\Console\Command;
use Atom\Console\ConsoleCommandProviderInterface;
use Atom\Console\ConsoleCommandSources;
use Atom\Console\CommandInterface;
use Atom\Console\ConsoleApplication;
use Atom\Console\ConsoleInput;
use Atom\Console\ConsoleOutput;
use Atom\Console\ConsoleServices;
use Atom\Console\Attributes\ConsoleCommand;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Di\ServiceProviderRegistry;
use Atom\Tests\Console\Fixtures\Commands\DiscoveredHelloCommand;
use PHPUnit\Framework\TestCase;

final class ConsoleApplicationTest extends TestCase
{
    public function testParsesCommandArgumentsAndOptions(): void
    {
        $input = ConsoleInput::fromArgv([
            "atom",
            "make:user",
            "Edin",
            "--role=admin",
            "--force",
            "-v",
        ]);

        $this->assertSame("make:user", $input->commandName());
        $this->assertSame("Edin", $input->argument(0));
        $this->assertSame("admin", $input->option("role"));
        $this->assertTrue($input->option("force"));
        $this->assertTrue($input->option("v"));
    }

    public function testRunsCommandResolvedFromInjector(): void
    {
        $bindings = Bindings::create()
            ->value(ConsoleGreetingService::class, new ConsoleGreetingService("Hello"));

        $console = new ConsoleApplication(Injector::create($bindings));
        $console->command("greet", ConsoleGreetingCommand::class, "Greets a user");

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "greet", "Atom", "--loud"], $output);

        $this->assertSame(0, $code);
        $this->assertSame("HELLO, ATOM!" . PHP_EOL, $output->output());
    }

    public function testCanRunCommandInstance(): void
    {
        $console = new ConsoleApplication(Injector::create());
        $console->command("ping", new ConsolePingCommand());

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "ping"], $output);

        $this->assertSame(0, $code);
        $this->assertSame("pong" . PHP_EOL, $output->output());
    }

    public function testCanRunBaseCommandClass(): void
    {
        $bindings = Bindings::create()
            ->value(ConsoleGreetingService::class, new ConsoleGreetingService("Made"));

        $console = new ConsoleApplication(Injector::create($bindings));
        $console->add(ConsoleMakeCommand::class);

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "make:thing", "Article", "--force"], $output);

        $this->assertSame(0, $code);
        $this->assertSame("MADE, ARTICLE! with force" . PHP_EOL, $output->output());
    }

    public function testBaseCommandCanInjectOutputInConstructor(): void
    {
        $console = new ConsoleApplication(Injector::create());
        $console->add(ConsoleConstructorOutputCommand::class);

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "output:test"], $output);

        $this->assertSame(0, $code);
        $this->assertSame("constructor output" . PHP_EOL, $output->output());
    }

    public function testCanAttachCommandMethodsAndBindParameters(): void
    {
        $bindings = Bindings::create()
            ->value(ConsoleGreetingService::class, new ConsoleGreetingService("Hello"));

        $console = new ConsoleApplication(Injector::create($bindings));
        $console->attach(ConsoleUserCommands::class);

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "user:greet", "Edin", "--times=2", "--loud"], $output);

        $this->assertSame(0, $code);
        $this->assertSame("HELLO, EDIN!" . PHP_EOL . "HELLO, EDIN!" . PHP_EOL, $output->output());
    }

    public function testAttachedMethodCommandsCanUseTypedServices(): void
    {
        $bindings = Bindings::create()
            ->value(ConsoleGreetingService::class, new ConsoleGreetingService("Hi"));

        $console = new ConsoleApplication(Injector::create($bindings));
        $console->attach(ConsoleUserCommands::class);

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "user:service", "Atom"], $output);

        $this->assertSame(0, $code);
        $this->assertSame("Hi, Atom!" . PHP_EOL, $output->output());
    }

    public function testAttachedCommandGroupCanInjectOutputInConstructor(): void
    {
        $bindings = Bindings::create()
            ->value(ConsoleGreetingService::class, new ConsoleGreetingService("Hi"));

        $console = new ConsoleApplication(Injector::create($bindings));
        $console->attach(ConsoleOutputUserCommands::class);

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "user:count"], $output);

        $this->assertSame(0, $code);
        $this->assertSame("42" . PHP_EOL, $output->output());
    }

    public function testRendersHelp(): void
    {
        $console = new ConsoleApplication(Injector::create());
        $console->command("ping", new ConsolePingCommand(), "Checks console wiring");

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "help"], $output);

        $this->assertSame(0, $code);
        $this->assertStringContainsString("Available commands:", $output->output());
        $this->assertStringContainsString("ping  Checks console wiring", $output->output());
    }

    public function testUnknownCommandReturnsFailure(): void
    {
        $console = new ConsoleApplication(Injector::create());
        $output = new BufferedConsoleOutput();

        $code = $console->run(["atom", "missing"], $output);

        $this->assertSame(1, $code);
        $this->assertStringContainsString("Command 'missing' was not found.", $output->errors());
    }

    public function testDiscoversCommandClassesAndCommandGroups(): void
    {
        $console = new ConsoleApplication(Injector::create());
        $console->discover(__DIR__ . "/Fixtures/Commands", "Atom\\Tests\\Console\\Fixtures\\Commands");

        $output = new BufferedConsoleOutput();
        $helloCode = $console->run(["atom", "discovered:hello", "Atom"], $output);

        $this->assertSame(0, $helloCode);
        $this->assertSame("Hello, Atom!" . PHP_EOL, $output->output());

        $output = new BufferedConsoleOutput();
        $userCode = $console->run(["atom", "discovered:user", "Edin"], $output);

        $this->assertSame(0, $userCode);
        $this->assertSame("User Edin" . PHP_EOL, $output->output());
        $this->assertTrue($console->commands()->has(DiscoveredHelloCommand::name()));
        $this->assertFalse($console->commands()->has("not:a-command"));
    }

    public function testDiscoversCommandSourcesProvidedByServices(): void
    {
        $providers = ServiceProviderRegistry::create()
            ->add(ConsoleServices::class)
            ->add(new ConsoleFixtureCommandProvider());
        $bindings = $providers->bindings()
            ->value(ServiceProviderRegistry::class, $providers);

        $console = Injector::create($bindings)->get(ConsoleApplication::class);

        $this->assertTrue($console->commands()->has("atom:about"));
        $this->assertTrue($console->commands()->has(DiscoveredHelloCommand::name()));

        $output = new BufferedConsoleOutput();
        $code = $console->run(["atom", "discovered:hello", "Atom"], $output);

        $this->assertSame(0, $code);
        $this->assertSame("Hello, Atom!" . PHP_EOL, $output->output());
    }
}

final readonly class ConsoleGreetingService
{
    public function __construct(private string $prefix)
    {
    }

    public function greet(string $name): string
    {
        return "{$this->prefix}, {$name}!";
    }
}

final readonly class ConsoleGreetingCommand implements CommandInterface
{
    public function __construct(private ConsoleGreetingService $greeting)
    {
    }

    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $message = $this->greeting->greet($input->argument(0, "World") ?? "World");

        if ($input->hasOption("loud")) {
            $message = strtoupper($message);
        }

        $output->line($message);
        return 0;
    }
}

final class ConsolePingCommand implements CommandInterface
{
    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        $output->line("pong");
        return 0;
    }
}

final class ConsoleMakeCommand extends Command
{
    protected static string $name = "make:thing";
    protected static string $description = "Makes a thing";

    protected function execute(string $name, bool $force, ConsoleGreetingService $greeting): int
    {
        $suffix = $force ? " with force" : "";
        $this->line(strtoupper($greeting->greet($name)) . $suffix);

        return 0;
    }
}

final class ConsoleConstructorOutputCommand extends Command
{
    protected static string $name = "output:test";

    public function __construct(private ConsoleOutput $console)
    {
    }

    protected function execute(): int
    {
        $this->console->line("constructor output");
        return 0;
    }
}

final readonly class ConsoleUserCommands
{
    public function __construct(private ConsoleGreetingService $greeting)
    {
    }

    #[ConsoleCommand("user:greet", "Greets a user")]
    public function greet(string $name, int $times = 1, bool $loud = false): string
    {
        $message = $this->greeting->greet($name);

        if ($loud) {
            $message = strtoupper($message);
        }

        return str_repeat($message . PHP_EOL, $times);
    }

    #[ConsoleCommand("user:service", "Greets a user through explicit service parameter")]
    public function service(string $name, ConsoleGreetingService $greeting, ConsoleOutput $output): int
    {
        $output->line($greeting->greet($name));
        return 0;
    }
}

final readonly class ConsoleOutputUserCommands
{
    public function __construct(
        private ConsoleOutput $output,
        private ConsoleGreetingService $greeting
    ) {
    }

    #[ConsoleCommand("user:count", "Counts users")]
    public function count(): int
    {
        $this->greeting->greet("Atom");
        $this->output->line("42");

        return 0;
    }
}

final class ConsoleFixtureCommandProvider implements ServiceProviderInterface, ConsoleCommandProviderInterface
{
    public function register(Bindings $bindings): void
    {
    }

    public function consoleCommands(ConsoleCommandSources $commands): void
    {
        $commands->add(__DIR__ . "/Fixtures/Commands", "Atom\\Tests\\Console\\Fixtures\\Commands");
    }
}
