<?php

declare(strict_types=1);

namespace Atom\Container\TypeFactory;

use Atom\Container\TypeInfo;
use ReflectionFunction;

final class TypeMatcher implements ITypeMatcher
{
    private ReflectionFunction $callable;

    public function __construct(callable $callable)
    {
        $this->callable = new ReflectionFunction($callable);
    }

    public function matches(TypeInfo $typeInfo): bool
    {
        return $this->callable->invoke($typeInfo);
    }
}
