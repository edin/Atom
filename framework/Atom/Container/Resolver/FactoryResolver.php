<?php

namespace Atom\Container\Resolver;

use Atom\Container\ComponentRegistration;
use Atom\Container\ResolutionContext;

final class FactoryResolver implements IDependencyResolver
{
    public function __construct(ComponentRegistration $registration)
    {
        $this->registration = $registration;
        $this->factory = $registration->factory;
        $this->reflectionFunction = new \ReflectionFunction($this->factory);
        $this->dependencies = $registration->getContainer()->getDependencyResolver()->getClosureDependencies($this->factory);
    }

    public function resolve(ResolutionContext $context = null, array $params = [])
    {
        $args = [];

        foreach ($this->dependencies as $parameter) {
            $args[$parameter->index] = $params[$parameter->name] ?? $parameter->defaultValue;

            if ($parameter->typeName && !$parameter->isBuiltinType) {
                $resolver = $this->registration->getContainer()->getResolver($parameter->typeName);

                $args[$parameter->index] = $resolver->resolve($context, $params);
            }
        }

        return $this->reflectionFunction->invokeArgs($args);
    }

    public function getRegistration(): ComponentRegistration
    {
        return $this->registration;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function resolveType(): ?string
    {
        return null;
    }
}
