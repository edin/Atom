<?php

declare(strict_types=1);

namespace Atom\Console;

final readonly class ConsoleCommandSource
{
    public function __construct(
        public string $directory,
        public string $namespace
    ) {
    }
}
