<?php

declare(strict_types=1);

namespace Atom\Hydrator;

use ReflectionClass;

final class HydrationPlanFactory
{
    /** @var array<string, HydrationPlan> */
    private array $plans = [];

    public function for(string $className): HydrationPlan
    {
        if (isset($this->plans[$className])) {
            return $this->plans[$className];
        }

        $reflection = new ReflectionClass($className);
        $constructorParameters = [];
        $promoted = [];
        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $parameter) {
                $constructorParameters[] = HydrationTarget::fromParameter($parameter);
                if ($parameter->isPromoted()) {
                    $promoted[$parameter->getName()] = true;
                }
            }
        }
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || $property->isReadOnly() || isset($promoted[$property->getName()])) {
                continue;
            }

            $properties[] = HydrationTarget::fromProperty($property);
        }

        return $this->plans[$className] = new HydrationPlan($reflection, $constructorParameters, $properties);
    }
}
