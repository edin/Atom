<?php

declare(strict_types=1);

namespace Atom\Cache;

use Closure;
use DateInterval;
use DateTimeImmutable;
use Throwable;

final readonly class FileCache implements CacheInterface, PrunableCacheInterface
{
    private string $namespace;
    private Closure $clock;

    public function __construct(
        private string $directory,
        private string $prefix = "atom",
        private int $defaultTtl = 0,
        ?callable $clock = null
    ) {
        if ($directory === "") {
            throw new CacheException("Cache directory cannot be empty.");
        }
        if ($defaultTtl < 0) {
            throw new CacheException("Default cache TTL cannot be negative.");
        }
        $this->namespace = hash("sha256", $prefix);
        $this->clock = $clock === null
            ? static fn(): int => time()
            : Closure::fromCallable($clock);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->withKeyLock($key, function (string $path) use ($default): mixed {
            $item = $this->read($path);
            return $item["found"] ? $item["value"] : $default;
        });
    }

    public function has(string $key): bool
    {
        return $this->withKeyLock($key, fn(string $path): bool => $this->read($path)["found"]);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): void
    {
        $expiresAt = $this->expiresAt($ttl);
        $this->withKeyLock($key, function (string $path) use ($value, $expiresAt): void {
            if ($expiresAt !== null && $expiresAt <= $this->now()) {
                $this->remove($path);
                return;
            }
            $this->write($path, $value, $expiresAt);
        });
    }

    public function delete(string $key): void
    {
        $this->withKeyLock($key, function (string $path): void {
            $this->remove($path);
        });
    }

    public function clear(): void
    {
        $this->withGlobalLock(LOCK_EX, function (): void {
            $directory = $this->dataDirectory();
            if (!is_dir($directory)) {
                return;
            }
            foreach (glob($directory . DIRECTORY_SEPARATOR . "*" . DIRECTORY_SEPARATOR . "*.cache") ?: [] as $path) {
                $this->remove($path);
            }
            foreach (glob($directory . DIRECTORY_SEPARATOR . "*") ?: [] as $path) {
                if (is_dir($path)) {
                    @rmdir($path);
                }
            }
            @rmdir($directory);
        });
    }

    public function prune(): int
    {
        return $this->withGlobalLock(LOCK_EX, function (): int {
            $removed = 0;
            $directory = $this->dataDirectory();
            if (!is_dir($directory)) {
                return 0;
            }

            foreach (glob($directory . DIRECTORY_SEPARATOR . "*" . DIRECTORY_SEPARATOR . "*.cache") ?: [] as $path) {
                $this->read($path);
                if (!is_file($path)) {
                    $removed++;
                }
            }
            $this->removeEmptyDataDirectories();

            return $removed;
        });
    }

    public function remember(string $key, DateInterval|int|null $ttl, callable $factory): mixed
    {
        return $this->withKeyLock($key, function (string $path) use ($ttl, $factory): mixed {
            $item = $this->read($path);
            if ($item["found"]) {
                return $item["value"];
            }

            $value = $factory();
            $expiresAt = $this->expiresAt($ttl);
            if ($expiresAt === null || $expiresAt > $this->now()) {
                $this->write($path, $value, $expiresAt);
            }
            return $value;
        });
    }

    public function add(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return $this->withKeyLock($key, function (string $path) use ($value, $ttl): bool {
            if ($this->read($path)["found"]) {
                return false;
            }
            $expiresAt = $this->expiresAt($ttl);
            if ($expiresAt !== null && $expiresAt <= $this->now()) {
                return false;
            }
            $this->write($path, $value, $expiresAt);
            return true;
        });
    }

    public function increment(string $key, int $amount = 1, DateInterval|int|null $ttl = null): int
    {
        return $this->withKeyLock($key, function (string $path) use ($amount, $ttl): int {
            $item = $this->read($path);
            if (!$item["found"]) {
                $value = $amount;
                $expiresAt = $this->expiresAt($ttl);
            } else {
                if (!is_int($item["value"])) {
                    throw new CacheException("Cache value must be an integer to increment it.");
                }
                $value = $item["value"] + $amount;
                if (!is_int($value)) {
                    throw new CacheException("Cache increment overflowed the integer range.");
                }
                $expiresAt = $item["expiresAt"];
            }
            if ($expiresAt === null || $expiresAt > $this->now()) {
                $this->write($path, $value, $expiresAt);
            }
            return $value;
        });
    }

    /** @return array{found: bool, value: mixed, expiresAt: int|null} */
    private function read(string $path): array
    {
        if (!is_file($path)) {
            return ["found" => false, "value" => null, "expiresAt" => null];
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new CacheException("Cannot read cache file '{$path}'.");
        }

        $item = @unserialize($contents, ["allowed_classes" => true]);
        if (!is_array($item) || !array_key_exists("value", $item)
            || !array_key_exists("expiresAt", $item)
            || ($item["expiresAt"] !== null && !is_int($item["expiresAt"]))) {
            $this->remove($path);
            return ["found" => false, "value" => null, "expiresAt" => null];
        }
        if ($item["expiresAt"] !== null && $item["expiresAt"] <= $this->now()) {
            $this->remove($path);
            return ["found" => false, "value" => null, "expiresAt" => null];
        }

        return ["found" => true, "value" => $item["value"], "expiresAt" => $item["expiresAt"]];
    }

    private function write(string $path, mixed $value, ?int $expiresAt): void
    {
        $this->ensureDirectory(dirname($path));
        try {
            $contents = serialize(["expiresAt" => $expiresAt, "value" => $value]);
        } catch (Throwable $exception) {
            throw new CacheException("Cache value could not be serialized.", 0, $exception);
        }

        $temporary = $path . "." . bin2hex(random_bytes(6)) . ".tmp";
        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new CacheException("Cannot write temporary cache file '{$temporary}'.");
        }
        if (!@rename($temporary, $path)) {
            if (is_file($path) && !@unlink($path)) {
                @unlink($temporary);
                throw new CacheException("Cannot replace cache file '{$path}'.");
            }
            if (!@rename($temporary, $path)) {
                @unlink($temporary);
                throw new CacheException("Cannot move cache file into place '{$path}'.");
            }
        }
    }

    private function remove(string $path): void
    {
        if (is_file($path) && !@unlink($path)) {
            throw new CacheException("Cannot delete cache file '{$path}'.");
        }
    }

    private function expiresAt(DateInterval|int|null $ttl): ?int
    {
        $ttl ??= $this->defaultTtl;
        if ($ttl instanceof DateInterval) {
            return (new DateTimeImmutable("@" . $this->now()))->add($ttl)->getTimestamp();
        }
        return $ttl === 0 ? null : $this->now() + $ttl;
    }

    private function now(): int
    {
        return ($this->clock)();
    }

    private function withKeyLock(string $key, callable $callback): mixed
    {
        $hash = $this->keyHash($key);
        return $this->withGlobalLock(LOCK_SH, function () use ($hash, $callback): mixed {
            $lockPath = $this->lockDirectory() . DIRECTORY_SEPARATOR . $hash . ".lock";
            $this->ensureDirectory(dirname($lockPath));
            $handle = fopen($lockPath, "c+");
            if ($handle === false || !flock($handle, LOCK_EX)) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
                throw new CacheException("Cannot lock cache key.");
            }
            try {
                $path = $this->dataDirectory() . DIRECTORY_SEPARATOR . substr($hash, 0, 2)
                    . DIRECTORY_SEPARATOR . $hash . ".cache";
                return $callback($path);
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        });
    }

    private function withGlobalLock(int $operation, callable $callback): mixed
    {
        $this->ensureDirectory($this->directory);
        $path = $this->directory . DIRECTORY_SEPARATOR . ".cache.lock";
        $handle = fopen($path, "c+");
        if ($handle === false || !flock($handle, $operation)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new CacheException("Cannot acquire global cache lock.");
        }
        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function keyHash(string $key): string
    {
        if ($key === "" || preg_match('/[\x00-\x1F\x7F]/', $key) === 1) {
            throw new CacheException("Cache key cannot be empty or contain control characters.");
        }
        return hash("sha256", $this->prefix . ":" . $key);
    }

    private function dataDirectory(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $this->namespace;
    }

    private function lockDirectory(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . "locks" . DIRECTORY_SEPARATOR . $this->namespace;
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new CacheException("Cannot create cache directory '{$directory}'.");
        }
    }

    private function removeEmptyDataDirectories(): void
    {
        $directory = $this->dataDirectory();
        foreach (glob($directory . DIRECTORY_SEPARATOR . "*") ?: [] as $path) {
            if (is_dir($path)) {
                @rmdir($path);
            }
        }
        @rmdir($directory);
    }
}
