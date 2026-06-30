<?php

declare(strict_types=1);

namespace Atom\Support;

use InvalidArgumentException;

final class Paths
{
    /** @var array<string, string> */
    private array $aliases = [];

    public function __construct(string $root = "")
    {
        if ($root !== "") {
            $this->alias("root", $root);
        }
    }

    public function alias(string $name, string $path): self
    {
        $this->aliases[$this->normalizeAlias($name)] = rtrim(str_replace("\\", "/", $path), "/");

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->aliases[$this->normalizeAlias($name)]);
    }

    public function get(string $name): string
    {
        $name = $this->normalizeAlias($name);

        return $this->aliases[$name] ?? throw new InvalidArgumentException("Path alias '@{$name}' is not registered.");
    }

    public function resolve(string $path): string
    {
        $path = str_replace("\\", "/", $path);
        if ($path === "" || $this->isAbsolute($path) || $path[0] !== "@") {
            return $path;
        }

        if (!preg_match('/^@([A-Za-z_][A-Za-z0-9_]*)(?:\/(.*))?$/', $path, $matches)) {
            throw new InvalidArgumentException("Path alias expression '{$path}' is invalid.");
        }

        $base = $this->get($matches[1]);
        $suffix = $matches[2] ?? "";

        return $suffix === "" ? $base : $base . "/" . $suffix;
    }

    public function resolveFrom(string $base, string $path): string
    {
        $path = str_replace("\\", "/", $path);
        if ($path === "" || $this->isAbsolute($path) || $path[0] === "@") {
            return $this->resolve($path);
        }

        return rtrim($this->resolve($base), "/") . "/" . ltrim($path, "/");
    }

    private function normalizeAlias(string $name): string
    {
        $name = ltrim($name, "@");
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Path alias '{$name}' is invalid.");
        }

        return $name;
    }

    private function isAbsolute(string $path): bool
    {
        return preg_match('/^(?:[A-Za-z]:\/|\/)/', $path) === 1;
    }
}
