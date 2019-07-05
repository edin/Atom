<?php

namespace Atom\Container;

use Atom\Container\Resolver\IDependencyResolver;

final class Container
{
    private $registry = [];
    private $resolvers = [];
    private $instances = [];
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

    public function getResolver($name): IDependencyResolver
    {
        if (!isset($this->resolvers[$name])) {
            $registration  = $this->registry[$name] ?? null;

            //TODO: Add search by name

            if ($registration !== null) {
                foreach ($registration->getResolvers() as $key => $value) {
                    $this->resolvers[$key] = $value;
                }
            }
        }

        if (!isset($this->resolvers[$name]) && !isset($this->registry[$name])) {
            $registration = $this->bind($name)->toSelf();
            foreach ($registration->getResolvers() as $key => $value) {
                print_r($value);
                exit;
                $this->resolvers[$key] = $value;
            }
        }

        return $this->resolvers[$name];
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
        if (isset($this->instances[$typeName])) {
            return $this->instances[$typeName];
        }

        $resolver = $this->getResolver($typeName);
        $registration = $resolver->getRegistration();

        $context = new ResolutionContext();
        $instance =  $resolver->resolve($context, $params);

        if ($registration->isShared) {
            $this->instances[$typeName] = $instance;
        }

        return $instance;
    }

    public function has($name): bool
    {
        if (isset($this->registry[$name])) {
            return true;
        }
        return false;
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
            $this->bind($definition)->toSelf()->asShared()->withName($name);
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
        return $this->get($name);
    }
}
