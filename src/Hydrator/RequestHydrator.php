<?php

declare(strict_types=1);

namespace Atom\Hydrator;

final readonly class RequestHydrator
{
    public function __construct(
        private HydrationPlanFactory $plans = new HydrationPlanFactory(),
        private ValueCoercer $coercer = new ValueCoercer()
    ) {
    }

    public function hydrate(string $className, HydrationContext $context): object
    {
        $plan = $this->plans->for($className);
        $instance = $plan->createInstance();

        foreach ($plan->properties as $property) {
            $value = $context->get($property->source ?? "auto", $property->sourceName);

            if ($value === null && $property->hasDefaultValue) {
                continue;
            }

            $property->setValue(
                $instance,
                $this->coercer->coerce($value, $property, $className)
            );
        }

        return $instance;
    }
}
