<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

use Atom\Database\Sql\Command;

final class SchemaCommandBatch
{
    /**
     * @param Command[] $commands
     */
    public function __construct(private array $commands = [])
    {
    }

    public function add(Command $command): self
    {
        $this->commands[] = $command;
        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->commands === [];
    }

    /**
     * @return Command[]
     */
    public function commands(): array
    {
        return $this->commands;
    }
}

