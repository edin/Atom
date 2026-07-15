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
        $arguments = [];
        foreach ($plan->constructorParameters as $parameter) {
            $resolved = $context->resolve($parameter->source ?? "auto", $parameter->sourceName);
            if (!$resolved["found"] && $parameter->hasDefaultValue) {
                $arguments[] = $parameter->defaultValue;
                continue;
            }
            $arguments[] = $this->coerce($resolved["value"], $parameter, $className);
        }
        $instance = $plan->createInstance($arguments);

        foreach ($plan->properties as $property) {
            $resolved = $context->resolve($property->source ?? "auto", $property->sourceName);

            if (!$resolved["found"] && $property->hasDefaultValue) {
                continue;
            }

            $property->setValue(
                $instance,
                $this->coerce($resolved["value"], $property, $className)
            );
        }

        return $instance;
    }

    private function coerce(mixed $value, HydrationTarget $target, string $className): mixed
    {
        return $this->coercer->coerce(
            $value,
            $target,
            $className,
            fn(string $nestedClass, array $data): object => $this->hydrate(
                $nestedClass,
                new HydrationContext(body: $data)
            )
        );
    }
}
