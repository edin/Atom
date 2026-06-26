<?php

declare(strict_types=1);

namespace Atom\Tests\Console\Fixtures\Commands;

use Atom\Console\Attributes\ConsoleCommand;

final class DiscoveredUserCommands
{
    #[ConsoleCommand("discovered:user", "Discovered user command")]
    public function user(string $name): string
    {
        return "User {$name}" . PHP_EOL;
    }
}

