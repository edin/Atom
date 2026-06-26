<?php

declare(strict_types=1);

namespace Atom\Console;

final class BufferedConsoleOutput extends ConsoleOutput
{
    private string $output = "";
    private string $error = "";

    public function __construct()
    {
    }

    public function write(string $message): void
    {
        $this->output .= $message;
    }

    public function error(string $message): void
    {
        $this->error .= $message;
    }

    public function output(): string
    {
        return $this->output;
    }

    public function errors(): string
    {
        return $this->error;
    }

    protected function supportsColor(): bool
    {
        return false;
    }
}
