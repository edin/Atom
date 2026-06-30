<?php

declare(strict_types=1);

namespace Atom\Config;

use BackedEnum;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use UnitEnum;

final class Config
{
    /** @var array<class-string, object> */
    private array $items = [];

    /**
     * @param array<string, string|null>|null $env
     */
    public function __construct(private ?array $env = null)
    {
    }

    /**
     * @param array<string, string|null>|null $env
     */
    public static function fromEnv(?array $env = null): self
    {
        return new self($env);
    }

    public function set(object $options): self
    {
        $this->items[$options::class] = $options;

        return $this;
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    public function get(string $type): object
    {
        return $this->items[$type] ?? throw new RuntimeException("Options '{$type}' were not registered.");
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T|null
     */
    public function maybe(string $type): ?object
    {
        return $this->items[$type] ?? null;
    }

    public function has(string $type): bool
    {
        return isset($this->items[$type]);
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    public function options(string $type): object
    {
        if (isset($this->items[$type])) {
            return $this->items[$type];
        }

        $options = $this->hydrate($type);
        $this->set($options);

        return $options;
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    private function hydrate(string $type): object
    {
        $reflection = new ReflectionClass($type);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $prefix = $this->prefix($reflection);
        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $arguments[$parameter->getPosition()] = $this->parameterValue($parameter, $prefix, $type);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function prefix(ReflectionClass $reflection): string
    {
        $attribute = $reflection->getAttributes(Options::class)[0] ?? null;

        return $attribute === null ? "" : $attribute->newInstance()->prefix;
    }

    private function parameterValue(ReflectionParameter $parameter, string $prefix, string $type): mixed
    {
        $key = $prefix . $this->envName($parameter);
        $value = $this->env($key);

        if ($value === null) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            if ($parameter->allowsNull()) {
                return null;
            }

            throw new RuntimeException("Missing environment option '{$key}' for {$type}::\${$parameter->getName()}.");
        }

        return $this->coerce($value, $parameter);
    }

    private function envName(ReflectionParameter $parameter): string
    {
        $attribute = $parameter->getAttributes(FromEnv::class)[0] ?? null;
        if ($attribute !== null) {
            return $attribute->newInstance()->name;
        }

        return $this->snakeUpper($parameter->getName());
    }

    private function env(string $key): ?string
    {
        if ($this->env !== null) {
            return array_key_exists($key, $this->env) ? $this->env[$key] : null;
        }

        return Env::get($key);
    }

    private function coerce(string $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            if ($type->allowsNull() && trim($value) === "") {
                return null;
            }

            $name = $type->getName();
            return match ($name) {
                "string" => $value,
                "int" => (int) $value,
                "float" => (float) $value,
                "bool" => $this->bool($value),
                default => $this->objectValue($name, $value),
            };
        }

        return $value;
    }

    private function objectValue(string $type, string $value): mixed
    {
        if (is_subclass_of($type, BackedEnum::class)) {
            return $type::from($value);
        }

        if (is_subclass_of($type, UnitEnum::class)) {
            foreach ($type::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
        }

        throw new RuntimeException("Cannot hydrate environment option as '{$type}'.");
    }

    private function bool(string $value): bool
    {
        return match (strtolower(trim($value))) {
            "1", "true", "yes", "on" => true,
            "0", "false", "no", "off", "" => false,
            default => (bool) $value,
        };
    }

    private function snakeUpper(string $name): string
    {
        return strtoupper((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
