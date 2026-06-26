<?php

declare(strict_types=1);

namespace Atom\Console;

final readonly class ConsoleInput
{
    /**
     * @param string[] $arguments
     * @param array<string, string|bool> $options
     */
    public function __construct(
        private string $commandName,
        private array $arguments = [],
        private array $options = []
    ) {
    }

    /**
     * @param string[] $argv
     */
    public static function fromArgv(array $argv): self
    {
        array_shift($argv);
        $commandName = array_shift($argv) ?? "help";
        $arguments = [];
        $options = [];

        foreach ($argv as $token) {
            if (str_starts_with($token, "--")) {
                $option = substr($token, 2);
                [$name, $value] = self::parseOption($option);
                $options[$name] = $value;
                continue;
            }

            if (str_starts_with($token, "-") && strlen($token) > 1) {
                $options[substr($token, 1)] = true;
                continue;
            }

            $arguments[] = $token;
        }

        return new self($commandName, $arguments, $options);
    }

    /**
     * @return array{0: string, 1: string|bool}
     */
    private static function parseOption(string $option): array
    {
        if (str_contains($option, "=")) {
            [$name, $value] = explode("=", $option, 2);
            return [$name, $value];
        }

        return [$option, true];
    }

    public function commandName(): string
    {
        return $this->commandName;
    }

    public function argument(int $index, ?string $default = null): ?string
    {
        return $this->arguments[$index] ?? $default;
    }

    /**
     * @return string[]
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function option(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * @return array<string, string|bool>
     */
    public function options(): array
    {
        return $this->options;
    }
}

