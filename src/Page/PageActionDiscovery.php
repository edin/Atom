<?php

declare(strict_types=1);

namespace Atom\Page;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

final readonly class PageActionDiscovery
{
    public function __construct(private int $maxDepth = 2)
    {
    }

    /**
     * @param class-string<Page> $pageClass
     * @return string[]
     */
    public function methods(string $pageClass): array
    {
        $methods = [];

        foreach ($this->targets(new ReflectionClass($pageClass)) as $target) {
            foreach ($this->targetMethods($target) as $method) {
                $methods[$method] = $method;
            }
        }

        return array_values($methods);
    }

    /**
     * @return ReflectionClass[]
     */
    private function targets(ReflectionClass $page): array
    {
        return $this->collectTargets($page, 0, []);
    }

    /**
     * @param array<class-string, true> $visited
     * @return ReflectionClass[]
     */
    private function collectTargets(ReflectionClass $class, int $depth, array $visited): array
    {
        $className = $class->getName();
        if (isset($visited[$className])) {
            return [];
        }

        $visited[$className] = true;
        $targets = [$class];

        if ($depth >= $this->maxDepth) {
            return $targets;
        }

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $type = $property->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $propertyClassName = $type->getName();
            if (!class_exists($propertyClassName)) {
                continue;
            }

            $targets = [
                ...$targets,
                ...$this->collectTargets(new ReflectionClass($propertyClassName), $depth + 1, $visited),
            ];
        }

        return $targets;
    }

    /**
     * @return string[]
     */
    private function targetMethods(ReflectionClass $reflection): array
    {
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === Page::class) {
                continue;
            }

            foreach ($method->getAttributes(PageAction::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $action = $attribute->newInstance();
                $methods[strtoupper($action->method)] = strtoupper($action->method);
            }
        }

        return array_values($methods);
    }
}
