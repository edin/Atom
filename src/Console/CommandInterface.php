<?php

declare(strict_types=1);

namespace Atom\Console;

interface CommandInterface
{
    public function handle(ConsoleInput $input, ConsoleOutput $output): int;
}

