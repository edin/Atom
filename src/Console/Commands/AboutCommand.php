<?php

declare(strict_types=1);

namespace Atom\Console\Commands;

use Atom\Console\Command;

final class AboutCommand extends Command
{
    protected static string $name = "atom:about";
    protected static string $description = "Shows framework information";

    protected function execute(): string
    {
        return "Atom Framework" . PHP_EOL;
    }
}
