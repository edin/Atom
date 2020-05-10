<?php

namespace Atom\Hydrator;

use ReflectionClass;

abstract class AbstractHydrator implements IHydrator
{
    protected $reflection;
    protected $properties;

    public function __construct(string $typeName)
    {
        $this->reflection = new ReflectionClass($typeName);
        $this->properties = $this->reflection->getProperties();
        foreach ($this->properties as $prop) {
            $prop->setAccessible(true);
        }
    }

    abstract function hydrate(array $data);

    function extract(object $instance): array
    {
        $result = [];
        foreach ($this->properties as $prop) {
            $result[$prop->getName()] = $prop->getValue($instance);
        }
        return $result;
    }
}
