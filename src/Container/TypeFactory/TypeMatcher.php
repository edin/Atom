<?php

declare(strict_types=1);

namespace Atom\Container\TypeFactory;

use Atom\Container\TypeInfo;

final class TypeMatcher implements ITypeMatcher
{
    private $callable = null;

    public function __construct(callable $callable)
    {
        $this->callable = new \ReflectionFunction($callable);
    }

    public function matches(TypeInfo $typeInfo): bool
    {
        return $this->callable->invoke($typeInfo);
    }
}
