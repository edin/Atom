<?php

namespace Atom\Bindings;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;

final class BindingProperty implements BindingTargetInterface
{
    private ReflectionProperty $property;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param ReflectionProperty $property
     * @param mixed $value
     */
    public function __construct(ReflectionProperty $property, $value = null)
    {
        $this->property = $property;
        $this->value = $value;
    }

    public function getTarget()
    {
        return $this->property;
    }

    public function getName(): string
    {
        return $this->property->getName();
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getType(): ?ReflectionType
    {
        if (PHP_VERSION_ID >= 70000 && $this->property->hasType()) {
            return $this->property->getType();
        }
        return null;
    }

    public function getTypeName(): string
    {
        $type = $this->getType();
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }
        return null;
    }

    public function isArray(): bool
    {
        return $this->getTypeName() === "array";
    }

    public function isBuiltin(): bool
    {
        $type = $this->getType();
        if ($type instanceof ReflectionNamedType) {
            return $type->isBuiltin();
        }
        return false;
    }

    public function allowsNull(): bool
    {
        if ($type = $this->getType()) {
            return $type->allowsNull();
        }
        return true;
    }

    public function isInstanceOf($typeName): bool
    {
        $propertyTypeName = $this->getTypeName();
        if ($propertyTypeName) {
            return is_a($propertyTypeName, $typeName, true);
        }
        return false;
    }

    public function hasDefaultValue(): bool
    {
        if (PHP_VERSION_ID >= 80000) {
            return $this->property->hasDefaultValue();
        }
        return true;
    }

    public function getDefaultValue()
    {
        if (PHP_VERSION_ID >= 80000) {
            return $this->property->getDefaultValue();
        }

        $reflectionClass = new ReflectionClass($this->property->class);
        $defaultProperties = $reflectionClass->getDefaultProperties();

        if (isset($defaultProperties[$this->property->name])) {
            return $defaultProperties[$this->property->name];
        }
        return null;
    }
}
