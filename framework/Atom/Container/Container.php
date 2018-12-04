<?php

namespace Atom\Container;

final class Container
{
    private $registry = [];
    private $instances = [];
    private $namespaceRegistry = [];
    private $instanceRegistry = [];

    private function createInstance(Registration $registration)
    {
        switch($registration->type) {
            case Registration::INSTANCE:
                return $registration->instance;
            case Registration::CLASS_NAME;
                //TODO: Resolve constructor params
                //TODO: Inject dependencies
                return new $registration->className;
            case Registration::FACTORY_METHOD;
                return call_user_func($registration->factory, $this);
        }
        throw new \Exception("Type '$registration->type' is not supported by createInstance method.");
    }

    public function set(string $name, $definition)
    {
        $registration = Registration::create($definition);
        $registration->name = $name;
        $registration->isShared = true;
        $this->registry[$name] = $registration;
    }

    public function get($name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (!$this->has($name)) {
            throw new \Exception("Can't find definition for '$name' depdendency.");
        }

        $registration = $this->registry[$name];
        $instance = $this->createInstance($registration);

        if ($registration->isShared) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    public function has($name)
    {
        return isset($this->registry[$name]);
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    // public function namespaceOf($namespace, $factory)
    // {
    //     $this->namespaceRegistry[$namespace] = $factory;
    // }

    // public function instanceof ($classname, $factory) {
    //     $this->instanceRegistry[$classname] = $factory;
    // }

    public function resolveProperties(object $instance): void
    {
        $reflection = new \ReflectionClass($instance);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $value = $property->getValue($instance);
            $name = $property->getName();

            if (empty($value)) {
                if ($this->has($name)) {
                    $property->setValue($instance, $this->get($name));
                }
            }
        }
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

            if ($param->hasType()) {
                $typeClass = new \ReflectionClass($param->getType()->getName());
                $fullName = $typeClass->getName();
                $shortName = $typeClass->getShortName();

                if ($this->has($fullName)) {
                    $parameters[$paramPos] = $this->get($fullName);
                } elseif ($this->has($shortName)) {
                    $parameters[$paramPos] = $this->get($shortName);
                }
            }
        };

        return $parameters;
    }
}