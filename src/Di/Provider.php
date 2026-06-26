<?php

declare(strict_types=1);

namespace Atom\Di;

final readonly class Provider
{
    private function __construct(
        public string $token,
        public ProviderKind $kind,
        public mixed $value,
        public ProviderLifetime $lifetime
    ) {
    }

    public static function type(
        string $token,
        ?string $className = null,
        ProviderLifetime $lifetime = ProviderLifetime::Transient
    ): self {
        return new self($token, ProviderKind::Type, $className ?? $token, $lifetime);
    }

    public static function value(string $token, mixed $value): self
    {
        return new self($token, ProviderKind::Value, $value, ProviderLifetime::Singleton);
    }

    public static function factory(
        string $token,
        callable $factory,
        ProviderLifetime $lifetime = ProviderLifetime::Transient
    ): self {
        return new self($token, ProviderKind::Factory, $factory, $lifetime);
    }

    public static function existing(string $token, string $existingToken): self
    {
        return new self($token, ProviderKind::Existing, $existingToken, ProviderLifetime::Transient);
    }

    public function scoped(): self
    {
        return new self($this->token, $this->kind, $this->value, ProviderLifetime::Scoped);
    }

    public function singleton(): self
    {
        return new self($this->token, $this->kind, $this->value, ProviderLifetime::Singleton);
    }

    public function transient(): self
    {
        return new self($this->token, $this->kind, $this->value, ProviderLifetime::Transient);
    }
}
