<?php

declare(strict_types=1);

namespace Atom\Container\TypeFactory;

use Atom\Container\Container;
use Atom\Container\TypeInfo;

final class TypeFactoryRegistry
{
    private array $registry = [];

    public function registerFactory($typeFactory, $typeMatcher): void
    {
        if ($typeMatcher instanceof \Closure) {
            $typeMatcher = new TypeMatcher($typeMatcher);
        }
        $this->registry[] = [$typeMatcher, $typeFactory];
    }

    public function canCreateType(string $typeName)
    {
        $typeInfo = new TypeInfo($typeName);

        foreach ($this->registry as $item) {
            $matcher = $item[0];
            if ($matcher->matches($typeInfo)) {
                return true;
            }
        }
        return false;
    }

    public function createType(Container $container, string $typeName): ?object
    {
        $typeInfo = new TypeInfo($typeName);
        foreach ($this->registry as $item) {
            $matcher = $item[0];
            $factoryType = $item[1];

            if ($matcher->matches($typeInfo)) {
                $factory = is_object($factoryType) ? $factoryType : $container->resolve($factoryType);
                return $factory->create($typeName);
            }
        }
        return null;
    }
}
