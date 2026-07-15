<?php

declare(strict_types=1);

namespace Atom\Session;

final class FlashBag
{
    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function put(string $key, mixed $value): void
    {
        $state = $this->state();
        $state["next"][$key] = $value;
        $this->write($state);
    }

    public function now(string $key, mixed $value): void
    {
        $state = $this->state();
        $state["current"][$key] = $value;
        $this->write($state);
    }

    public function has(string $key): bool
    {
        $state = $this->state();
        return array_key_exists($key, $state["next"]) || array_key_exists($key, $state["current"]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $state = $this->state();
        if (array_key_exists($key, $state["next"])) {
            return $state["next"][$key];
        }

        return array_key_exists($key, $state["current"]) ? $state["current"][$key] : $default;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $state = $this->state();
        $value = $this->get($key, $default);
        unset($state["next"][$key], $state["current"][$key]);
        $this->write($state);
        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $state = $this->state();
        return [...$state["current"], ...$state["next"]];
    }

    public function keep(string $key): void
    {
        $state = $this->state();
        if (array_key_exists($key, $state["current"])) {
            $state["next"][$key] = $state["current"][$key];
            $this->write($state);
        }
    }

    public function reflash(): void
    {
        $state = $this->state();
        $state["next"] = [...$state["current"], ...$state["next"]];
        $this->write($state);
    }

    /**
     * @return array{current: array<string, mixed>, next: array<string, mixed>}
     */
    private function state(): array
    {
        return FlashData::state($this->session->get(FlashData::KEY));
    }

    /**
     * @param array{current: array<string, mixed>, next: array<string, mixed>} $state
     */
    private function write(array $state): void
    {
        $this->session->put(FlashData::KEY, $state);
    }
}
