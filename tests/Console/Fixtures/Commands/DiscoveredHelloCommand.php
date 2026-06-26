<?php

declare(strict_types=1);

namespace Atom\Tests\Console\Fixtures\Commands;

use Atom\Console\Command;

final class DiscoveredHelloCommand extends Command
{
    protected static string $name = "discovered:hello";
    protected static string $description = "Discovered hello command";

    protected function execute(string $name = "World"): string
    {
        return "Hello, {$name}!" . PHP_EOL;
    }
}

