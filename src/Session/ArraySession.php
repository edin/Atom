<?php

declare(strict_types=1);

namespace Atom\Session;

final class ArraySession implements SessionInterface
{
    /** @var array<string, mixed> */
    private array $data;
    private bool $started = false;
    private string $sessionId;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [], ?string $id = null)
    {
        $this->data = $data;
        $this->sessionId = $id ?? bin2hex(random_bytes(16));
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        FlashData::age($this->data);
        $this->started = true;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function id(): string
    {
        $this->start();
        return $this->sessionId;
    }

    public function all(): array
    {
        $this->start();
        return array_diff_key($this->data, [FlashData::KEY => true]);
    }

    public function has(string $key): bool
    {
        $this->start();
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->start();
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($this->data[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }

    public function clear(): void
    {
        $this->start();
        $this->data = [];
    }

    public function regenerate(bool $deleteOldSession = false): void
    {
        $this->start();
        $this->sessionId = bin2hex(random_bytes(16));
    }

    public function invalidate(): void
    {
        $this->clear();
        $this->regenerate(true);
    }

    public function save(): void
    {
        $this->started = false;
    }
}
