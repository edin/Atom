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
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || $property->isReadOnly()) {
                continue;
            }

            $properties[] = HydrationTarget::fromProperty($property);
        }

        return $this->plans[$className] = new HydrationPlan($reflection, $properties);
    }
}
