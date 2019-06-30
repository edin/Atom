<?php

namespace Atom\Container;

use ReflectionClass;
use ReflectionException;

final class Container
{
    private $registry = [];
    private $instances = [];
    private $reflections = [];
    private $dependencies = [];
    private $properties = [];

    public function bind(string $typeName): ComponentRegistration
    {
        $registration = new ComponentRegistration($typeName, $this);
        $this->registry[$typeName] = $registration;
        return $registration;
    }

    public function getReflectionClass(string $typeName): ?ReflectionClass
    {
        if (isset($this->reflections[$typeName])) {
            return $this->reflections[$typeName];
        }
        try {
            $reflection = new ReflectionClass($typeName);
            $this->reflections[$typeName] = $reflection;
        } catch (ReflectionException $ex) {
            $reflection = null;
        }
        return $reflection;
    }

    public function resolve(string $name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $scope = new ResolutionScope();
        return $this->resolveInternal($name, $scope);
    }

    private function resolveInternal(string $name, ResolutionScope $scope)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $scope->level ++;
        if ($scope->level > 10) {
            throw new CyclicDependencyException("Cyclic dependency or maximum level of recursion reached while trying to resolve dependencies.");
        }

        if (!$this->has($name)) {
            return $this->constructObject($name, [], [], $scope);
        }

        $registration = $this->registry[$name];

        switch ($registration->type) {
            case ComponentRegistration::FACTORY_METHOD:
                $method = $registration->factoryMethod;
                $parameters = $this->resolveMethodParametersInternal($method, $registration->constructorArguments, $scope);
                $instance = $method->invokeArgs($parameters);
                break;
            case ComponentRegistration::CLASS_NAME:
                $instance = $this->constructObject(
                    $registration->targetType,
                    $registration->constructorArguments,
                    $registration->properties,
                    $scope
                );
                break;
            case ComponentRegistration::INSTANCE:
                $instance = $registration->instance;
                break;
            default:
                throw new \Exception("Registration type is not supported by createInstance method.");
        }

        if ($registration->isShared) {
            $this->instances[$registration->sourceType] = $instance;
        }
        return $instance;
    }

    private function constructObject(string $className, array $params, array $properties, ResolutionScope $scope)
    {
        if ($scope->contains($className)) {
            return $scope->get($className);
        }

        $reflection =  $this->getReflectionClass($className);

        if ($reflection === null) {
            return null;
        }

        if (!$reflection->isInstantiable()) {
            throw new \Exception("{$className} is not instantiable.");
        }

        $parameters = $this->resolveConstructorParameters($reflection, $params, $scope);
        $instance = $reflection->newInstanceArgs($parameters);

        foreach ($properties as $key => $value) {
            if ($value instanceof Instance) {
                $instance->$key = $this->resolveInternal($value->getName(), $scope);
            } else {
                $instance->$key = $value;
            }
        }

        $scope->set($className, $instance);
        return $instance;
    }

    private function getParameterDependencies(?\ReflectionFunctionAbstract $method): array
    {
        $parameters = [];

        if ($method === null) {
            return $parameters;
        }

        foreach ($method->getParameters() as $param) {
            $info = new \stdClass;
            $info->name = $param->getName();
            $info->position = $param->getPosition();
            $info->defaultValue = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            $info->typeName = null;
            if ($param->hasType() && !$param->getType()->isBuiltin()) {
                $info->typeName = $param->getType()->getName();
            }
            $parameters[$info->position] = $info;
        }
        return $parameters;
    }

    private function resolveConstructorParameters(\ReflectionClass $reflection, array $params, ResolutionScope $scope): array
    {
        $className = $reflection->getName();

        if (!isset($this->dependencies[$className])) {
            $this->dependencies[$className] = $this->getParameterDependencies($reflection->getConstructor());
        }

        $parameters  = [];
        $constructorParameters = $this->dependencies[$className];

        foreach ($constructorParameters as $index => $param) {
            $value = $params[$param->name] ?? null;

            if ($value instanceof Instance) {
                $parameters[$index] = $this->resolveInternal($value->getName(), $scope);
            } elseif ($param->typeName) {
                $parameters[$index] = $this->resolveInternal($param->typeName, $scope);
            } else {
                $parameters[$index] = $value ?? $param->defaultValue;
            }
        }

        return $parameters;
    }

    private function resolveMethodParametersInternal(\ReflectionFunctionAbstract $method, array $params, ResolutionScope $scope): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $param) {
            $paramName = $param->getName();
            $paramPos = $param->getPosition();

            if (isset($params[$paramName])) {
                $parameters[$paramPos] = $params[$paramName];
            } else {
                $parameters[$paramPos] = ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
            }

            if ($param->hasType() && !$param->getType()->isBuiltin()) {
                $typeName = $param->getType()->getName();
                $parameters[$paramPos] = $this->resolveInternal($typeName, $scope);
            }
        }
        return $parameters;
    }

    public function resolveMethodParameters(\ReflectionFunctionAbstract $method, array $params): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $param) {
            $paramName = $param->getName();
            $paramPos = $param->getPosition();

            if (isset($params[$paramName])) {
                $parameters[$paramPos] = $params[$paramName];
            } else {
                $parameters[$paramPos] = ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
            }

            if ($param->hasType() && !$param->getType()->isBuiltin()) {
                $typeName = $param->getType()->getName();
                $parameters[$paramPos] = $this->resolve($typeName);
            }
        }
        return $parameters;
    }

    public function has($name): bool
    {
        return isset($this->registry[$name]);
    }

    public function get($name)
    {
        // TODO: Review this hack
        foreach($this->registry as $d) {
            if ($d->name == $name) {
                return $this->resolve($d->sourceType);
            }
        }

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
        }

        if ($definition instanceof ComponentRegistration) {
            $this->set($name, $definition);
            return;
        }

        if (is_array($definition)) {
            throw new \Exception("Array configuration is not yet supported.");
            return;
        }

        if (is_string($name) && is_string($definition)) {
            if (class_exists($definition)  && !class_exists($name)) {
                $this->bind($definition)->toSelf()->asShared()->withName($name);
                return;
            }
        }

        if (is_string($definition)) {
            $this->bind($name)->to($definition)->asShared();
            return;
        }

        if ($definition instanceof \Closure) {
            $this->bind($name)->toFactory($definition);
            return;
        }

        $this->bind($name)->toInstance($definition);
    }

    public function __get($name)
    {
        return $this->get($name);
    }
}