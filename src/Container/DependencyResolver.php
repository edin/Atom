<?php

namespace Atom\Container;

final class DependencyResolver
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getProperties(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        $classProperties =  $reflection->getProperties(\ReflectionMethod::IS_PUBLIC);
        $defaultProperties = $reflection->getDefaultProperties();

        $properties = [];

        foreach ($classProperties as $prop) {
            $info = new DependencyInfo;
            $info->name = $prop->getName();
            $info->position = null;
            $info->defaultValue = $defaultProperties[$info->name] ?? null;

            // NOTE: Requires PHP 7.4
            $info->typeName = null;

            $properties[$info->name] = $info;
        }
        return $properties;
    }

    public function getMethodDependencies(string $className, string $methodName): array
    {
        $reflection = new \ReflectionClass($className);
        return $this->getFunctionDependencies($reflection->getMethod($methodName));
    }

    public function getConstructorDependencies(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        return  $this->getFunctionDependencies($reflection->getConstructor());
    }

    public function getClosureDependencies(\Closure $closure): array
    {
        return $this->getFunctionDependencies(new \ReflectionFunction($closure));
    }

    public function getFunctionDependencies(?\ReflectionFunctionAbstract $method): array
    {
        $parameters = [];

        if ($method === null) {
            return $parameters;
        }

        foreach ($method->getParameters() as $param) {
            $info = new DependencyInfo;
            $info->name = $param->getName();
            $info->position = $param->getPosition();
            $info->defaultValue = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            $info->typeName = null;

            if ($param->hasType()) {
                $info->typeName = $param->getType()->getName();
                $info->isBuiltinType = $param->getType()->isBuiltin();
                if (!$info->isBuiltinType) {
                    $info->resolvedType = $this->container->resolveType($info->typeName);
                }
            }

            $parameters[] = $info;
        }
        return $parameters;
    }
}
