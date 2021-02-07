<?php

namespace Atom\Bindings;

use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

interface BindingTargetInterface
{
    /**
     * @return ReflectionParameter|ReflectionProperty
     */
    public function getTarget();

    public function getName(): string;

    /**
     * @return mixed
     */
    public function getValue();

    public function getType(): ?ReflectionType;

    public function getTypeName(): ?string;

    public function isArray(): bool;

    public function isInstanceOf(string $typeName): bool;

    public function isBuiltin(): bool;

    public function allowsNull(): bool;

    public function hasDefaultValue(): bool;

    /**
     * @return mixed
     */
    public function getDefaultValue();
}
