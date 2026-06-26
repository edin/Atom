<?php

declare(strict_types=1);

namespace Atom\Di;

final class InjectionContext
{
    /** @var array<string, mixed> */
    private array $instances = [];
    /** @var string[] */
    private array $resolving = [];

    public function has(string $token): bool
    {
        return array_key_exists($token, $this->instances);
    }

    public function get(string $token): mixed
    {
        return $this->instances[$token] ?? null;
    }

    public function set(string $token, mixed $instance): void
    {
        $this->instances[$token] = $instance;
    }

    public function isResolving(string $token): bool
    {
        return in_array($token, $this->resolving, true);
    }

    public function enter(string $token): void
    {
        $this->resolving[] = $token;
    }

    public function leave(string $token): void
    {
        for ($i = count($this->resolving) - 1; $i >= 0; $i--) {
            if ($this->resolving[$i] === $token) {
                array_splice($this->resolving, $i, 1);
                return;
            }
        }
    }

    /**
     * @return string[]
     */
    public function resolvingPath(?string $token = null): array
    {
        return $token === null ? $this->resolving : [...$this->resolving, $token];
    }
}
