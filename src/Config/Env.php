<?php

declare(strict_types=1);

namespace Atom\Config;

final class Env
{
    public static function loadIfExists(string $path, bool $override = false): bool
    {
        if (!is_file($path)) {
            return false;
        }

        self::load($path, $override);

        return true;
    }

    public static function load(string $path, bool $override = false): void
    {
        if (!is_file($path)) {
            throw new EnvException("Environment file '{$path}' does not exist.");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new EnvException("Environment file '{$path}' could not be read.");
        }

        foreach ($lines as $line) {
            $entry = self::parseLine($line);
            if ($entry === null) {
                continue;
            }

            [$key, $value] = $entry;
            if (!$override && self::has($key)) {
                continue;
            }

            self::put($key, $value);
        }
    }

    public static function has(string $key): bool
    {
        return getenv($key) !== false || array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER);
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (array_key_exists($key, $_ENV)) {
            return (string) $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return (string) $_SERVER[$key];
        }

        return $default;
    }

    public static function string(string $key, string $default = ""): string
    {
        return self::get($key, $default) ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return $value === null || trim($value) === "" ? $default : (int) $value;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::get($key);

        return $value === null || trim($value) === "" ? $default : (float) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return match (strtolower(trim($value))) {
            "1", "true", "yes", "on" => true,
            "0", "false", "no", "off", "" => false,
            default => $default,
        };
    }

    private static function put(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function parseLine(string $line): ?array
    {
        $line = trim($line);
        if ($line === "" || str_starts_with($line, "#")) {
            return null;
        }

        if (str_starts_with($line, "export ")) {
            $line = trim(substr($line, 7));
        }

        if (!str_contains($line, "=")) {
            throw new EnvException("Environment line must contain '='.");
        }

        [$key, $value] = array_map("trim", explode("=", $line, 2));
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            throw new EnvException("Environment key '{$key}' is invalid.");
        }

        return [$key, self::parseValue($value)];
    }

    private static function parseValue(string $value): string
    {
        if ($value === "") {
            return "";
        }

        $quote = $value[0];
        if (($quote === '"' || $quote === "'") && str_ends_with($value, $quote)) {
            $value = substr($value, 1, -1);
        }

        return str_replace(["\\n", "\\r", "\\t"], ["\n", "\r", "\t"], $value);
    }
}
