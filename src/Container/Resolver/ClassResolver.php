<?php

declare(strict_types=1);

namespace Atom\Container\Resolver;

use Atom\Container\ComponentRegistration;
use Atom\Container\Instance;
use Atom\Container\ResolutionContext;
use ReflectionClass;

final class ClassResolver implements IDependencyResolver
{
    private $registration;
    private $reflectionClass;
    private $dependencies;
    private $container;
    private $dependencyResolver;

    public function __construct(ComponentRegistration $registration)
    {
        $this->registration = $registration;
        $this->container = $registration->getContainer();
        $this->dependencyResolver = $registration->getContainer()->getDependencyResolver();
        $this->reflectionClass = new ReflectionClass($registration->targetType);
        $this->dependencies = $this->dependencyResolver->getConstructorDependencies($registration->targetType);

        foreach ($this->dependencies as $key => $parameter) {
            $value = $registration->constructorArguments[$parameter->name] ?? null;

            if ($value !== null) {
                if ($value instanceof Instance) {
                    $parameter->resolvedType = $value->getName();
                    $parameter->useResolvedType = true;
                } else {
                    $parameter->defaultValue = $value;
                }
            }
        }

        if (!$this->reflectionClass->isInstantiable()) {
            throw new \Exception("Class {$registration->targetType} is not instantiable.");
        }
    }

    public function getFactory(ResolutionContext $context, array $params): ClassTypeFactory
    {
        return new ClassTypeFactory(
            $this->registration,
            $context,
            $params,
            $this->dependencies,
            $this->reflectionClass
        );
    }

    public function resolve(ResolutionContext $context = null, array $params = [])
    {
        $sourceType = $this->registration->sourceType;

        if ($context->contains($sourceType)) {
            return $context->get($sourceType);
        }

        $args = [];

        foreach ($this->dependencies as $index => $parameter) {
            $value = $params[$parameter->name] ?? null;

            if ($parameter->isBuiltinType) {
                $args[$index] = $value ?? $parameter->defaultValue;
            } elseif ($parameter->resolvedType && $parameter->useResolvedType) {
                $args[$index] = $this->container->resolveInContext($context, $parameter->typeName);
            } elseif ($parameter->typeName) {
                $args[$index] = $this->container->resolveInContext($context, $parameter->typeName);
            }
        }

        $instance = $this->reflectionClass->newInstanceArgs($args);

        foreach ($this->registration->properties as $key => $value) {
            if ($value instanceof Instance) {
                $instance->$key = $this->container->getResolver($value->getName())->resolve($context);
            } else {
                $instance->$key = $value;
            }
        }

        $context->set($sourceType, $instance);
        return $instance;
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
        return $this->registration->targetType;
    }
}
