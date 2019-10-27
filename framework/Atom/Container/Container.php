<?php

namespace Atom\Container;

use Atom\Container\Resolver\IDependencyResolver;
use ReflectionClass;

final class Container
{
    private $registry = [];
    private $resolvers = [];
    private $instances = [];
    private $alias = [];
    private $dependencyResolver;

    public function __construct()
    {
        $this->dependencyResolver = new DependencyResolver($this);
    }

    public function getDependencyResolver(): DependencyResolver
    {
        return $this->dependencyResolver;
    }

    public function getDefinition($name): ComponentRegistration
    {
        return $this->registry[$name];
    }

    public function createType(string $targetType): object {

        $context = new ResolutionContext();

        $reflectionClass = new ReflectionClass($targetType);
        if (!$reflectionClass->isInstantiable()) {
            throw new \Exception("Class {$targetType} is not instantiable.");
        }

        $args = [];
        $dependencies = $this->dependencyResolver->getConstructorDependencies($targetType);
        foreach ($dependencies as $index => $parameter) {
            //$value = $params[$parameter->name] ?? null;
            if ($parameter->resolvedType) {
                $args[$index] = $this->getResolver($parameter->resolvedType)->resolve($context, []);
            } elseif ($parameter->typeName && !$parameter->isBuiltinType) {
                $args[$index] = $this->getResolver($parameter->typeName)->resolve($context, []);
            } else {
                $args[$index] = /*$value ??*/ $parameter->defaultValue;
            }
        }

        $instance = $reflectionClass->newInstanceArgs($args);

        return $instance;
    }

    public function getResolver($typeName): IDependencyResolver
    {
        //TODO: Refactor getResolver to return only single resolver

        if (isset($this->alias[$typeName])) {
            $typeName = $this->alias[$typeName];
        }

        if (!isset($this->resolvers[$typeName])) {
            $registration  = $this->registry[$typeName] ?? null;
            if ($registration !== null) {
                foreach ($registration->getResolvers() as $key => $value) {
                    $this->resolvers[$key] = $value;
                }
            }
        }

        if (!isset($this->resolvers[$typeName]) && !isset($this->registry[$typeName])) {
            $typeFactory = $this->get("TypeFactory");

            if ($typeFactory->canCreateType($typeName)) {
                $registration = $this->bind($typeName)->toSelf()->toTypeFactory($typeFactory);
                foreach ($registration->getResolvers() as $key => $value) {
                    $this->resolvers[$key] = $value;
                }
            } else {
                $registration = $this->bind($typeName)->toSelf();
                foreach ($registration->getResolvers() as $key => $value) {
                    $this->resolvers[$key] = $value;
                }
            }
        }

        return $this->resolvers[$typeName];
    }

    public function alias(string $alias, string $target)
    {
        $this->alias[$alias] = $target;
    }

    public function bind(string $typeName): ComponentRegistration
    {
        $this->registry[$typeName] = $registration = new ComponentRegistration($typeName, $this);
        return $registration;
    }

    public function setInstance(string $name, $value)
    {
        $this->instances[$name] = $value;
    }

    public function resolveType(string $typeName)
    {
        return $this->getResolver($typeName)->resolveType();
    }

    public function resolve(string $typeName, $params = [])
    {
        if (isset($this->alias[$typeName])) {
            $typeName = $this->alias[$typeName];
        }

        if (isset($this->instances[$typeName])) {
            return $this->instances[$typeName];
        }

        $context = new ResolutionContext();
        return $this->resolveInContext($context, $typeName, $params);
    }

    public function resolveWithProperties(string $typeName, $params = [])
    {
        $context = new ResolutionContext();
        $instance = $this->resolveInContext($context, $typeName, $params);

        $properties = $this->dependencyResolver->getProperties(get_class($instance));

        foreach ($properties as $propertyDependency) {
            if ($propertyDependency->defaultValue === null) {
                $instance->{$propertyDependency->name} = $this->get($propertyDependency->name);
            }
        }

        return $instance;
    }

    public function resolveMethodParameters(?\ReflectionFunctionAbstract $method, array $params = []): array
    {
        $context = new ResolutionContext();

        $result = [];
        $parameters = $this->dependencyResolver->getFunctionDependencies($method);

        foreach ($parameters as $parameterDependency) {
            if (isset($params[$parameterDependency->name])) {
                $result[$parameterDependency->position] = $params[$parameterDependency->name];
            } else {
                $result[$parameterDependency->position] = $parameterDependency->defaultValue;
            }

            if ($parameterDependency->resolvedType) {
                $result[$parameterDependency->position] = $this->resolveInContext($context, $parameterDependency->resolvedType);
            }
        }

        return $result;
    }

    public function resolveInContext(ResolutionContext $context, string $typeName, array $params = [])
    {
        if (isset($this->alias[$typeName])) {
            $typeName = $this->alias[$typeName];
        }

        if (isset($this->instances[$typeName])) {
            return $this->instances[$typeName];
        }

        $resolver = $this->getResolver($typeName);
        $registration = $resolver->getRegistration();


        $instance = $resolver->resolve($context, $params);

        if ($registration->isShared) {
            $this->instances[$typeName] = $instance;
        }
        return $instance;
    }

    public function has($name): bool
    {
        return isset($this->registry[$name]);
    }

    public function get($name)
    {
        return $this->resolve($name);
    }

    public function set($name, $definition)
    {
        $this->registry[$name] = $definition;
    }

    public function __set($name, $definition)
    {
        if (empty($definition)) {
            throw new \Exception("Component definition can't be null or empty, it must be class name, factory method or instance.");
        } elseif ($definition instanceof ComponentRegistration) {
            $this->set($name, $definition);
        } elseif (is_array($definition)) {
            throw new \Exception("Array configuration is not yet supported.");
        } elseif (is_string($name) && is_string($definition)  && (class_exists($definition)  && !class_exists($name))) {
            $this->bind($definition)->toSelf()->asShared();
            $this->alias($name, $definition);
        } elseif (is_string($definition)) {
            $this->bind($name)->to($definition)->asShared();
        } elseif ($definition instanceof \Closure) {
            $this->bind($name)->toFactory($definition);
        } else {
            $this->bind($name)->toInstance($definition);
        }
    }

    public function __get($name)
    {
        return $this->resolve($name);
    }
}
