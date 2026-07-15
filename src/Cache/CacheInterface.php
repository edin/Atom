<?php

declare(strict_types=1);

namespace Atom\Cache;

use DateInterval;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): void;

    public function delete(string $key): void;

    public function clear(): void;

    public function remember(string $key, DateInterval|int|null $ttl, callable $factory): mixed;

    public function add(string $key, mixed $value, DateInterval|int|null $ttl = null): bool;

    public function increment(string $key, int $amount = 1, DateInterval|int|null $ttl = null): int;
}
