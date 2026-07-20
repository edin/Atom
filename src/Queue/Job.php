<?php

declare(strict_types=1);

namespace Atom\Queue;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

abstract readonly class Job implements JobInterface
{
    abstract public static function type(): string;

    public function payload(): array
    {
        $reflection = new ReflectionClass($this);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return [];
        }

        $payload = [];
        foreach ($constructor->getParameters() as $parameter) {
            $this->assertSupportedParameter($parameter);
            $property = $this->payloadProperty($reflection, $parameter);
            $value = $property->getValue($this);
            $this->assertJsonSafe($value, $parameter->getName());
            $payload[$parameter->getName()] = $value;
        }

        return $payload;
    }

    public static function fromPayload(array $payload): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            if ($payload !== []) {
                throw new QueueException("Job '" . static::type() . "' does not accept payload fields.");
            }

            return $reflection->newInstance();
        }

        $parameters = [];
        foreach ($constructor->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        $unknown = array_diff(array_keys($payload), array_keys($parameters));
        if ($unknown !== []) {
            throw new QueueException(
                "Job '" . static::type() . "' received unknown payload field '" . reset($unknown) . "'."
            );
        }

        $arguments = [];
        foreach ($parameters as $name => $parameter) {
            self::assertSupportedParameter($parameter);
            self::payloadProperty($reflection, $parameter);

            if (!array_key_exists($name, $payload)) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new QueueException(
                    "Job '" . static::type() . "' is missing required payload field '{$name}'."
                );
            }

            $arguments[] = self::validatedValue($parameter, $payload[$name]);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     */
    private static function payloadProperty(
        ReflectionClass $reflection,
        ReflectionParameter $parameter
    ): ReflectionProperty {
        $name = $parameter->getName();
        if (!$reflection->hasProperty($name)) {
            throw new QueueException(
                "Job '" . static::type() . "' constructor parameter '{$name}' must have a corresponding property."
            );
        }

        $property = $reflection->getProperty($name);
        if ($property->isStatic()) {
            throw new QueueException(
                "Job '" . static::type() . "' payload property '{$name}' cannot be static."
            );
        }

        return $property;
    }

    private static function assertSupportedParameter(ReflectionParameter $parameter): void
    {
        $name = $parameter->getName();
        if ($parameter->isVariadic()) {
            throw new QueueException(
                "Job '" . static::type() . "' payload parameter '{$name}' cannot be variadic."
            );
        }

        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            throw new QueueException(
                "Job '" . static::type() . "' payload parameter '{$name}' must use a built-in type."
            );
        }

        if (!in_array($type->getName(), ["string", "int", "float", "bool", "array", "mixed"], true)) {
            throw new QueueException(
                "Job '" . static::type() . "' payload parameter '{$name}' uses unsupported type '"
                . $type->getName()
                . "'."
            );
        }
    }

    private static function validatedValue(ReflectionParameter $parameter, mixed $value): mixed
    {
        $name = $parameter->getName();
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType) {
            throw new QueueException("Job payload field '{$name}' has an unsupported type.");
        }

        if ($value === null) {
            if ($type->allowsNull() || $type->getName() === "mixed") {
                return null;
            }

            throw self::invalidType($name, $type->getName(), $value);
        }

        $valid = match ($type->getName()) {
            "string" => is_string($value),
            "int" => is_int($value),
            "float" => is_float($value) || is_int($value),
            "bool" => is_bool($value),
            "array" => is_array($value),
            "mixed" => true,
            default => false,
        };
        if (!$valid) {
            throw self::invalidType($name, $type->getName(), $value);
        }

        self::assertJsonSafe($value, $name);
        return $type->getName() === "float" ? (float) $value : $value;
    }

    private static function invalidType(string $name, string $expected, mixed $value): QueueException
    {
        return new QueueException(
            "Job '" . static::type() . "' payload field '{$name}' must be {$expected}; "
            . get_debug_type($value)
            . " given."
        );
    }

    private static function assertJsonSafe(mixed $value, string $path): void
    {
        if ($value === null || is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                self::assertJsonSafe($item, $path . "." . $key);
            }
            return;
        }

        throw new QueueException(
            "Job '" . static::type() . "' payload field '{$path}' must be JSON-safe; "
            . get_debug_type($value)
            . " given."
        );
    }
}
