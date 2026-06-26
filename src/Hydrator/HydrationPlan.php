<?php

declare(strict_types=1);

namespace Atom\Hydrator;

use ReflectionClass;

final readonly class HydrationPlan
{
    /**
     * @param HydrationTarget[] $properties
     */
    public function __construct(public ReflectionClass $reflection, public array $properties)
    {
    }

    public function createInstance(): object
    {
        $constructor = $this->reflection->getConstructor();
        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            return $this->reflection->newInstance();
        }

        return $this->reflection->newInstanceWithoutConstructor();
    }
}
