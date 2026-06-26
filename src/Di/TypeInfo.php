<?php

declare(strict_types=1);

namespace Atom\Di;

use ReflectionAttribute;
use ReflectionClass;

final readonly class TypeInfo
{
    private ReflectionClass $reflection;

    public function __construct(public string $name)
    {
        $this->reflection = new ReflectionClass($name);
    }

    public function isSubclassOf(string $className): bool
    {
        return $this->reflection->isSubclassOf($className);
    }

    public function inNamespace(string $namespace): bool
    {
        return str_starts_with($this->reflection->getNamespaceName(), $namespace);
    }

    public function hasAttribute(string $attributeClass): bool
    {
        return $this->reflection->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) !== [];
    }

    public function reflection(): ReflectionClass
    {
        return $this->reflection;
    }
}
