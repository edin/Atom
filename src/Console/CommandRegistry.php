<?php

declare(strict_types=1);

namespace Atom\Console;

use InvalidArgumentException;

final class CommandRegistry
{
    /** @var array<string, CommandDefinition> */
    private array $commands = [];

    /**
     * @param class-string<CommandInterface>|CommandInterface $command
     */
    public function add(string $name, string|CommandInterface $command, string $description = ""): self
    {
        if ($name === "") {
            throw new InvalidArgumentException("Console command name cannot be empty.");
        }

        if (isset($this->commands[$name])) {
            throw new InvalidArgumentException("Console command '{$name}' is already registered.");
        }

        $this->commands[$name] = new CommandDefinition($name, $command, $description);
        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    public function get(string $name): ?CommandDefinition
    {
        return $this->commands[$name] ?? null;
    }

    /**
     * @return CommandDefinition[]
     */
    public function all(): array
    {
        $commands = $this->commands;
        ksort($commands);

        return array_values($commands);
    }
}
