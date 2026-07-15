<?php

declare(strict_types=1);

namespace Atom\Session;

interface SessionInterface
{
    public function start(): void;

    public function isStarted(): bool;

    public function id(): string;

    /**
     * @return array<string, mixed>
     */
    public function all(): array;

    public function has(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value): void;

    public function remove(string $key): void;

    public function pull(string $key, mixed $default = null): mixed;

    public function clear(): void;

    public function regenerate(bool $deleteOldSession = false): void;

    public function invalidate(): void;

    public function save(): void;
}
