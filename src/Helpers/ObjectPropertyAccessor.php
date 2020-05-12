<?php

declare(strict_types=1);

namespace Atom\Helpers;

use ReflectionClass;

final class ObjectPropertyAccessor implements IPropertyAccessor
{
    private object $model;
    private ReflectionClass $reflection;

    public function __construct(object $model)
    {
        $this->model = $model;
        $this->reflection = new ReflectionClass($model);
    }

    public function getProperty($name)
    {
        $prop = $this->reflection->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($this->model);
    }

    public function setProperty($name, $value)
    {
        $prop = $this->reflection->getProperty($name);
        $prop->setAccessible(true);
        $prop->setValue($this->model, $value);
    }
}
