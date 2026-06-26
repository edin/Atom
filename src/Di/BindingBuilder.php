<?php

declare(strict_types=1);

namespace Atom\Di;

final readonly class BindingBuilder
{
    public function __construct(private Bindings $bindings, private string $token)
    {
    }

    public function to(?string $className = null): BindingRegistration
    {
        return $this->bindings->setProvider(Provider::type($this->token, $className));
    }

    public function toSelf(): BindingRegistration
    {
        return $this->to($this->token);
    }

    public function toValue(mixed $value): Bindings
    {
        $this->bindings->setProvider(Provider::value($this->token, $value));
        return $this->bindings;
    }

    public function toFactory(callable $factory): BindingRegistration
    {
        return $this->bindings->setProvider(Provider::factory($this->token, $factory));
    }

    public function toExisting(string $existingToken): Bindings
    {
        $this->bindings->setProvider(Provider::existing($this->token, $existingToken));
        return $this->bindings;
    }
}
