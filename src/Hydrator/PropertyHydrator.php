<?php

namespace Atom\Hydrator;

class PropertyHydrator extends AbstractHydrator
{
    public function hydrate(array $data)
    {
        $instance = $this->reflection->newInstanceArgs([]);
        foreach ($this->properties as $prop) {
            $name = $prop->getName();
            if (isset($data[$name])) {
                $prop->setValue($instance, $data[$name]);
            }
        }
        return $instance;
    }
}
