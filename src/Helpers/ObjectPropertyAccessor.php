<?php

namespace Atom\Helpers;

use ReflectionClass;

final class ObjectPropertyAccessor implements IPropetyAccessor
{
    private $model;
    private $reflection;

    public function __construct($model)
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
