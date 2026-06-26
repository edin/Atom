<?php

declare(strict_types=1);

namespace Atom\Console;

final readonly class CommandDefinition
{
    /**
     * @param class-string<CommandInterface>|CommandInterface $command
     */
    public function __construct(
        public string $name,
        public string|CommandInterface $command,
        public string $description = ""
    ) {
    }
}

