<?php

namespace Atom\Container\Resolver;

use Atom\Container\Container;
use Atom\Container\ResolutionContext;
use Atom\Container\ComponentRegistration;

final class AliasResolver implements IDependencyResolver
{
    private $registration;
    private $container;
    private $target;

    public function __construct(ComponentRegistration $registration)
    {
        $this->registration = $registration;
        $this->container = $registration->container;
        $this->target = $registration->targetType;
    }

    public function resolve(ResolutionContext $context, array $params = [])
    {
        return $this->container->getResolver($this->target)->resolve($context, $params);
    }

    public function getRegistration(): ComponentRegistration
    {
        return $this->registration;
    }

    public function getDependencies(): array
    {
        return $this->container->getResolver($this->target)->getDependencies();
    }

    public function resolveType(): ?string
    {
        return $this->container->getResolver($this->target)->resolveType();
    }
}
