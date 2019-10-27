<?php

namespace Atom\Container;

use ReflectionClass;

class TypeInfo
{
    private $typeName;

    public function __construct(string $typeName)
    {
        $this->typeName = $typeName;
        $this->type = new ReflectionClass($typeName);
    }

    public function isSubclassOf(string $typeName) {
        return $this->type->isSubclassOf($typeName);
    }

    public function inNamespace(string $namespace) {
        return strpos($this->type->getNamespaceName(), $namespace) === 0;
    }

    public function getType(): object {
        return $this->type;
    }

    public function getTypeName(): string {
        return $this->typeName;
    }
}