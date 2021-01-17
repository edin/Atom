<?php

declare(strict_types=1);

namespace Atom\Container;

final class DependencyInfo
{
    public $name;
    public $position;
    public $defaultValue;
    public $typeName;
    public $resolvedType;
    public $isBuiltinType;
    public $useResolvedType = false;
}
