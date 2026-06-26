<?php

declare(strict_types=1);

namespace Atom\Di;

final readonly class BindingRegistration
{
    public function __construct(private Bindings $bindings, private Provider $provider)
    {
    }

    public function scoped(): Bindings
    {
        $this->bindings->setProvider($this->provider->scoped());
        return $this->bindings;
    }

    public function singleton(): Bindings
    {
        $this->bindings->setProvider($this->provider->singleton());
        return $this->bindings;
    }

    public function transient(): Bindings
    {
        $this->bindings->setProvider($this->provider->transient());
        return $this->bindings;
    }
}
