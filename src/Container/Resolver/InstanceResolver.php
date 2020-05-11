<?php

declare(strict_types=1);

namespace Atom\Container\Resolver;

use Atom\Container\ComponentRegistration;
use Atom\Container\ResolutionContext;

final class InstanceResolver implements IDependencyResolver
{
    private $registration;
    private $instance;

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
        return get_class($this->instance);
    }
}
