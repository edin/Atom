<?php

declare(strict_types=1);

namespace Atom\Console;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use ReflectionMethod;

abstract class Command implements CommandInterface
{
    protected static string $name = "";
    protected static string $description = "";
    protected ?ConsoleInput $input = null;
    protected ?ConsoleOutput $output = null;

    public static function name(): string
    {
        return static::$name;
    }

    public static function description(): string
    {
        return static::$description;
    }

    final public function handle(ConsoleInput $input, ConsoleOutput $output): int
    {
        return $this->run(Injector::create(), $input, $output, new InjectionContext());
    }

    final public function run(
        Injector $injector,
        ConsoleInput $input,
        ConsoleOutput $output,
        InjectionContext $context
    ): int {
        $this->input = $input;
        $this->output = $output;

        $method = new ReflectionMethod($this, "execute");
        $parameters = (new ConsoleParameterBinder($injector, $context, $input, $output))
            ->bindPositional($method);
        $result = $method->invokeArgs($this, $parameters);

        if (is_string($result)) {
            $output->write($result);
        }

        return is_int($result) ? $result : 0;
    }

    protected function argument(int $index, ?string $default = null): ?string
    {
        return $this->input()->argument($index, $default);
    }

    /**
     * @return string[]
     */
    protected function arguments(): array
    {
        return $this->input()->arguments();
    }

    protected function option(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->input()->option($name, $default);
    }

    protected function hasOption(string $name): bool
    {
        return $this->input()->hasOption($name);
    }

    protected function write(string $message): void
    {
        $this->output()->write($message);
    }

    protected function line(string $message = ""): void
    {
        $this->output()->line($message);
    }

    protected function error(string $message): void
    {
        $this->output()->error($message);
    }

    protected function errorLine(string $message = ""): void
    {
        $this->output()->errorLine($message);
    }

    protected function input(): ConsoleInput
    {
        return $this->input ?? throw new \RuntimeException("Console command input is not available before handle().");
    }

    protected function output(): ConsoleOutput
    {
        return $this->output ?? throw new \RuntimeException("Console command output is not available before handle().");
    }
}
