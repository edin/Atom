<?php

declare(strict_types=1);

namespace Atom\Hydrator;

class ConstructorHydrator extends AbstractHydrator
{
    private $parameters = [];

    public function __construct(string $typeName)
    {
        parent::__construct($typeName);
        $constructor = $this->reflection->getConstructor();
        $this->parameters = $constructor->getParameters();
    }

    public function hydrate(array $data)
    {
        $args = [];
        foreach ($this->parameters as $param) {
            $defaultValue = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            $args[] = $data[$param->getName()] ?? $defaultValue;
        }
        return $this->reflection->newInstanceArgs($args);
    }
}
