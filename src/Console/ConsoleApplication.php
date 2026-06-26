<?php

declare(strict_types=1);

namespace Atom\Console;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Di\Provider;
use Atom\Console\Attributes\ConsoleCommand;
use ReflectionMethod;

final readonly class ConsoleApplication
{
    public function __construct(
        private Injector $injector,
        private CommandRegistry $commands = new CommandRegistry()
    ) {
    }

    /**
     * @param class-string<CommandInterface>|CommandInterface $command
     */
    public function command(string $name, string|CommandInterface $command, string $description = ""): self
    {
        $this->commands->add($name, $command, $description);
        return $this;
    }

    /**
     * @param class-string<Command> $command
     */
    public function add(string $command): self
    {
        $name = $command::name();

        if ($name === "") {
            throw new \InvalidArgumentException("Console command {$command} must define a non-empty name.");
        }

        $this->commands->add($name, $command, $command::description());
        return $this;
    }

    /**
     * @param class-string $className
     */
    public function attach(string $className): self
    {
        foreach ((new \ReflectionClass($className))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attribute = $method->getAttributes(ConsoleCommand::class)[0] ?? null;
            if ($attribute === null) {
                continue;
            }

            $command = $attribute->newInstance();
            $this->commands->add(
                $command->name,
                new MethodCommand($this->injector, $className, $method->getName()),
                $command->description
            );
        }

        return $this;
    }

    public function discover(string $directory, string $namespace): self
    {
        $discovery = new CommandDiscovery();

        foreach ($discovery->discover($directory, $namespace) as $className) {
            if (!$discovery->isDiscoverableCommand($className)) {
                continue;
            }

            if (is_subclass_of($className, Command::class)) {
                $this->add($className);
            } else {
                $this->attach($className);
            }
        }

        return $this;
    }

    public function discoverFrom(ConsoleCommandSources $sources): self
    {
        foreach ($sources as $source) {
            $this->discover($source->directory, $source->namespace);
        }

        return $this;
    }

    public function commands(): CommandRegistry
    {
        return $this->commands;
    }

    /**
     * @param string[] $argv
     */
    public function run(array $argv, ?ConsoleOutput $output = null): int
    {
        return $this->handle(ConsoleInput::fromArgv($argv), $output ?? new ConsoleOutput());
    }

    public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        if ($input->commandName() === "help" || $input->hasOption("help") || $input->hasOption("h")) {
            $this->renderHelp($output);
            return 0;
        }

        $definition = $this->commands->get($input->commandName());
        if ($definition === null) {
            $output->errorLine("Command '{$input->commandName()}' was not found.");
            $output->errorLine("");
            $this->renderHelp($output, true);
            return 1;
        }

        $commandInjector = $this->injector->createChild([
            Provider::value(ConsoleInput::class, $input),
            Provider::value(ConsoleOutput::class, $output),
        ]);
        $context = new InjectionContext();
        $context->set(ConsoleInput::class, $input);
        $context->set(ConsoleOutput::class, $output);

        $command = $this->resolve($definition, $commandInjector, $context);
        if ($command instanceof Command) {
            return $command->run($commandInjector, $input, $output, $context);
        }

        $result = $commandInjector->invoke(
            [$command, "handle"],
            ["input" => $input, "output" => $output],
            $context
        );

        return is_int($result) ? $result : 0;
    }

    private function resolve(
        CommandDefinition $definition,
        Injector $injector,
        InjectionContext $context
    ): CommandInterface
    {
        if ($definition->command instanceof CommandInterface) {
            return $definition->command;
        }

        return $injector->get($definition->command, $context);
    }

    private function renderHelp(ConsoleOutput $output, bool $error = false): void
    {
        $write = $error
            ? static fn(string $line = "") => $output->errorLine($line)
            : static fn(string $line = "") => $output->line($line);

        $write("Available commands:");

        $commands = $this->commands->all();
        $maxNameLength = strlen("help");

        foreach ($commands as $command) {
            $maxNameLength = max($maxNameLength, strlen($command->name));
        }

        foreach ($commands as $command) {
            $name = str_pad($command->name, $maxNameLength);
            $description = $command->description === "" ? "" : "  " . $output->muted($command->description);
            $write("  " . $output->command($name) . $description);
        }

        $write("  " . $output->command(str_pad("help", $maxNameLength)) . "  " . $output->muted("Show available commands"));
    }
}
