<?php

declare(strict_types=1);

namespace Atom\Helpers;

final class ArrayPropertyAccessor implements IPropertyAccessor
{
    private array $model;

    public function __construct(array &$model)
    {
        $this->model = $model;
    }

    public function getProperty($name)
    {
        return $this->model[$name];
    }

    public function setProperty($name, $value)
    {
        $this->model[$name] = $value;
    }
}
