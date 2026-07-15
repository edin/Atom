<?php

declare(strict_types=1);

namespace Atom\Hydrator;

use ReflectionClass;

final readonly class HydrationPlan
{
    /**
     * @param HydrationTarget[] $constructorParameters
     * @param HydrationTarget[] $properties
     */
    public function __construct(
        public ReflectionClass $reflection,
        public array $constructorParameters,
        public array $properties
    )
    {
    }

    /** @param mixed[] $arguments */
    public function createInstance(array $arguments = []): object
    {
        $constructor = $this->reflection->getConstructor();
        if ($constructor === null) {
            return $this->reflection->newInstance();
        }

        return $this->reflection->newInstanceArgs($arguments);
    }
}
