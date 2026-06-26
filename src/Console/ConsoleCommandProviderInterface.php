<?php

declare(strict_types=1);

namespace Atom\Console;

interface ConsoleCommandProviderInterface
{
    public function consoleCommands(ConsoleCommandSources $commands): void;
}
