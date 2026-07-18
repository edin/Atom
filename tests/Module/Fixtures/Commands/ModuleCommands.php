<?php

declare(strict_types=1);

namespace Atom\Tests\Module\Fixtures\Commands;

use Atom\Console\Attributes\ConsoleCommand;

final readonly class ModuleCommands
{
    #[ConsoleCommand("module:hello", "Run a command contributed by a module")]
    public function hello(string $name = "Atom"): string
    {
        return "Hello {$name} from module" . PHP_EOL;
    }
}
