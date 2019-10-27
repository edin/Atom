<?php

namespace Atom\Container\TypeFactory;

use Atom\Container\Container;
use Atom\Container\TypeInfo;

final class TypeFactoryRegistry
{
    private $registry = [];

    public function registerFactory($typeFactory, $typeMatcher)
    {
        if ($typeMatcher instanceof \Closure) {
            $typeMatcher = new TypeMatcher($typeMatcher);
        }
        $this->registry[] = [$typeMatcher, $typeFactory];
    }

    public function createType(Container $container, string $typeName) {
        $typeInfo = new TypeInfo($typeName);
        foreach($this->registry as $item) {
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
