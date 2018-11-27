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

    function instanceof ($classname, $factory) {
        $this->instanceRegistry[$classname] = $factory;
    }
}
