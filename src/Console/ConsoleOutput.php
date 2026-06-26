<?php

declare(strict_types=1);

namespace Atom\Console;

class ConsoleOutput
{
    private const RESET = "\033[0m";
    private const STYLES = [
        "command" => "\033[36m",
        "muted" => "\033[90m",
        "error" => "\033[31m",
    ];

    /**
     * @var resource
     */
    private mixed $output;

    /**
     * @var resource
     */
    private mixed $error;

    public function __construct(mixed $output = null, mixed $error = null)
    {
        $this->output = $output ?? fopen("php://stdout", "wb");
        $this->error = $error ?? fopen("php://stderr", "wb");
    }

    public function write(string $message): void
    {
        fwrite($this->output, $message);
    }

    public function line(string $message = ""): void
    {
        $this->write($message . PHP_EOL);
    }

    public function error(string $message): void
    {
        fwrite($this->error, $message);
    }

    public function errorLine(string $message = ""): void
    {
        $this->error($message . PHP_EOL);
    }

    public function style(string $message, string $style): string
    {
        if (!$this->supportsColor() || !isset(self::STYLES[$style])) {
            return $message;
        }

        return self::STYLES[$style] . $message . self::RESET;
    }

    public function command(string $message): string
    {
        return $this->style($message, "command");
    }

    public function muted(string $message): string
    {
        return $this->style($message, "muted");
    }

    public function errorText(string $message): string
    {
        return $this->style($message, "error");
    }

    protected function supportsColor(): bool
    {
        return true;
    }
}
