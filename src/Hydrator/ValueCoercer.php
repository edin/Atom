<?php

declare(strict_types=1);

namespace Atom\Hydrator;

use Atom\Hydrator\Exception\HydrationException;
use BackedEnum;
use ReflectionEnum;
use UnitEnum;

final class ValueCoercer
{
    public function coerce(
        mixed $value,
        HydrationTarget $property,
        string $className,
        ?callable $objectHydrator = null
    ): mixed
    {
        if (!$property->raw && is_string($value)) {
            $value = trim($value);
        }

        if ($value === "" && $property->allowsNull) {
            return null;
        }

        foreach ($property->transformers as $transformer) {
            $value = $transformer->transform($value);
        }

        if ($value === null) {
            if ($property->allowsNull) {
                return null;
            }

            throw new HydrationException("Property {$className}::{$property->name} is required.");
        }

        if ($property->typeName === null) {
            return $value;
        }

        if (!$property->isBuiltin) {
            return $this->coerceObject($value, $property, $className, $objectHydrator);
        }

        return $this->coerceBuiltin($value, $property, $className);
    }

    private function coerceBuiltin(mixed $value, HydrationTarget $property, string $className): mixed
    {
        return match ($property->typeName) {
            "string" => is_scalar($value) ? (string) $value : $this->fail($className, $property, "string", $value),
            "int" => filter_var($value, FILTER_VALIDATE_INT) !== false
                ? (int) $value
                : $this->fail($className, $property, "int", $value),
            "float" => filter_var($value, FILTER_VALIDATE_FLOAT) !== false
                ? (float) $value
                : $this->fail($className, $property, "float", $value),
            "bool" => ($bool = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)) !== null
                ? $bool
                : $this->fail($className, $property, "bool", $value),
            "array" => is_array($value) ? $value : [$value],
            default => $value,
        };
    }

    private function coerceObject(
        mixed $value,
        HydrationTarget $property,
        string $className,
        ?callable $objectHydrator
    ): mixed
    {
        if (is_object($value) && is_a($value, $property->typeName)) {
            return $value;
        }

        if (is_subclass_of($property->typeName, BackedEnum::class)) {
            if (!is_scalar($value)) {
                return $this->fail($className, $property, $property->typeName, $value);
            }
            $backingType = (new ReflectionEnum($property->typeName))->getBackingType()?->getName();
            if ($backingType === "int") {
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return $this->fail($className, $property, $property->typeName, $value);
                }
                $backingValue = (int) $value;
            } else {
                $backingValue = (string) $value;
            }
            return $property->typeName::tryFrom($backingValue)
                ?? $this->fail($className, $property, $property->typeName, $value);
        }

        if (is_subclass_of($property->typeName, UnitEnum::class) && is_string($value)) {
            foreach ($property->typeName::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
            return $this->fail($className, $property, $property->typeName, $value);
        }

        if (is_a($property->typeName, \DateTimeInterface::class, true)) {
            if ($value instanceof \DateTimeInterface) {
                return $value;
            }

            if (is_scalar($value)) {
                try {
                    return $property->typeName === \DateTimeImmutable::class
                        ? new \DateTimeImmutable((string) $value)
                        : new \DateTime((string) $value);
                } catch (\Exception) {
                    return $this->fail($className, $property, $property->typeName, $value);
                }
            }
        }

        if (is_array($value) && $objectHydrator !== null) {
            return $objectHydrator($property->typeName, $value);
        }

        return $this->fail($className, $property, $property->typeName, $value);
    }

    private function fail(string $className, HydrationTarget $property, string $expected, mixed $value): never
    {
        $type = get_debug_type($value);
        throw new HydrationException("Property {$className}::{$property->name} expected {$expected}, got {$type}.");
    }
}
