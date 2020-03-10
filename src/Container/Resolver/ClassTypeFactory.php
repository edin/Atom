<?php

namespace Atom\Container\Resolver;

use Atom\Container\ComponentRegistration;
use Atom\Container\Instance;
use Atom\Container\ResolutionContext;
use ReflectionClass;

class ClassTypeFactory
{
    private $container;
    private $registration;
    private $dependencies;
    private $arguments = [];
    private $properties = [];

    public function __construct(ComponentRegistration $registration, ResolutionContext $context, array $params, $dependencies, ReflectionClass $reflectionClass)
    {
        $this->container = $registration->getContainer();
        $this->registration = $registration;
        $this->dependencies = $dependencies;
        $this->reflectionClass = $reflectionClass;

        foreach ($this->dependencies as $index => $parameter) {
            $value = $params[$parameter->name] ?? null;
            if ($parameter->resolvedType) {
                $this->arguments[$index] = $this->container->getResolver($parameter->resolvedType)->resolve($context);
            } elseif ($parameter->typeName && !$parameter->isBuiltinType) {
                $this->arguments[$index] = $this->container->getResolver($parameter->typeName)->resolve($context);
            } else {
                $this->arguments[$index] = $value ?? $parameter->defaultValue;
            }
        }

        foreach ($this->registration->properties as $key => $value) {
            if ($value instanceof Instance) {
                $this->properties[$key] = $this->container->getResolver($value->getName())->resolve($this->context);
            } else {
                $this->properties[$key] = $value;
            }
        }
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function createInstance()
    {
        $arguments = $this->getArguments();
        $properties = $this->getProperties();
        $instance = $this->reflectionClass->newInstanceArgs($arguments);

        foreach ($properties as $key => $value) {
            $instance->{$key} = $value;
        }

        return $instance;
    }
}
