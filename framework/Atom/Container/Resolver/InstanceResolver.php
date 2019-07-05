<?php

namespace Atom\Container\Resolver;

use Atom\Container\ResolutionContext;
use Atom\Container\ComponentRegistration;

final class InstanceResolver implements IDependencyResolver
{
    public function __construct(ComponentRegistration $registration)
    {
        $this->registration = $registration;
        $this->instance = $registration->instance;
    }

    public function resolve(ResolutionContext $context, array $params = [])
    {
        return $this->instance;
    }

    public function getRegistration(): ComponentRegistration
    {
        return $this->registration;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function resolveType(): ?string
    {
        return null;
    }
}
