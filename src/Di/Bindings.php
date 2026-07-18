<?php

declare(strict_types=1);

namespace Atom\Di;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, Provider>
 */
final class Bindings implements IteratorAggregate
{
    /** @var array<string, Provider> */
    private array $providers = [];
    /** @var TypeFactory[] */
    private array $typeFactories = [];

    public static function create(): self
    {
        return new self();
    }

    public function bind(string $token): BindingBuilder
    {
        return new BindingBuilder($this, $token);
    }

    public function has(string $token): bool
    {
        return isset($this->providers[$token]);
    }

    public function type(string $token, ?string $className = null): BindingRegistration
    {
        return $this->bind($token)->to($className);
    }

    public function value(string $token, mixed $value): self
    {
        $this->setProvider(Provider::value($token, $value));
        return $this;
    }

    public function factory(string $token, callable $factory): BindingRegistration
    {
        return $this->bind($token)->toFactory($factory);
    }

    public function existing(string $token, string $existingToken): self
    {
        $this->setProvider(Provider::existing($token, $existingToken));
        return $this;
    }

    public function addTypeFactory(TypeFactory $typeFactory): self
    {
        $this->typeFactories[] = $typeFactory;
        return $this;
    }

    public function setProvider(Provider $provider): BindingRegistration
    {
        $this->providers[$provider->token] = $provider;
        return new BindingRegistration($this, $provider);
    }

    /**
     * @return array<string, Provider>
     */
    public function providers(): array
    {
        return $this->providers;
    }

    /**
     * @return TypeFactory[]
     */
    public function typeFactories(): array
    {
        return $this->typeFactories;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->providers);
    }
}
