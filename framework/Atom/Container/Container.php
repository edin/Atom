<?php

namespace Atom\Container;

final class Container
{
    private $registry = [];
    private $instances = [];
    private $namespaceRegistry = [];
    private $instanceRegistry = [];

    public function set(string $name, callable $factory)
    {
        $this->registry[$name] = $factory;
    }

    public function get($name)
    {
        if (!isset($this->instances[$name])) {
            if (isset($this->registry[$name])) {
                $factory = $this->registry[$name];
                $this->instances[$name] = call_user_func($factory, $this);
                return $this->instances[$name];
            } else {
                throw new \Exception("Can't find definition for '$name' depdendency.");
            }
        } else {
            return $this->instances[$name];
        }
        return null;
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

    public function namespaceOf($namespace, $factory)
    {
        $this->namespaceRegistry[$namespace] = $factory;
    }

    public function instanceof ($classname, $factory) {
        $this->instanceRegistry[$classname] = $factory;
    }

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