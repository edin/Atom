<?php

declare(strict_types=1);

namespace Atom\Console;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

final class ConsoleParameterBinder
{
    private int $argumentIndex = 0;

    public function __construct(
        private readonly Injector $injector,
        private readonly InjectionContext $context,
        private readonly ConsoleInput $input,
        private readonly ConsoleOutput $output
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function bindNamed(ReflectionFunctionAbstract $method): array
    {
        $parameters = [];

        foreach ($method->getParameters() as $parameter) {
            if ($this->canBind($parameter)) {
                $parameters[$parameter->getName()] = $this->bind($parameter);
            }
        }

        return $parameters;
    }

    /**
     * @return array<int, mixed>
     */
    public function bindPositional(ReflectionFunctionAbstract $method): array
    {
        $parameters = [];

        foreach ($method->getParameters() as $parameter) {
            $parameters[$parameter->getPosition()] = $this->bind($parameter);
        }

        return $parameters;
    }

    private function canBind(ReflectionParameter $parameter): bool
    {
        if ($parameter->getName() === "input" || $parameter->getName() === "output") {
            return true;
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return true;
        }

        if ($this->input->hasOption($parameter->getName())) {
            return true;
        }

        if ($this->input->argument($this->argumentIndex) !== null) {
            return true;
        }

        return $this->isBool($parameter) || $parameter->isDefaultValueAvailable() || $parameter->allowsNull();
    }

    private function bind(ReflectionParameter $parameter): mixed
    {
        $name = $parameter->getName();

        if ($name === "input") {
            return $this->input;
        }

        if ($name === "output") {
            return $this->output;
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->injector->get($type->getName(), $this->context);
        }

        if ($this->input->hasOption($name)) {
            return $this->coerce($this->input->option($name), $parameter);
        }

        $argument = $this->input->argument($this->argumentIndex);
        if ($argument !== null) {
            $this->argumentIndex++;
            return $this->coerce($argument, $parameter);
        }

        if ($this->isBool($parameter)) {
            return false;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new RuntimeException("Unable to bind console parameter '{$name}'.");
    }

    private function isBool(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        return $type instanceof ReflectionNamedType && $type->getName() === "bool";
    }

    private function coerce(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            return $value;
        }

        return match ($type->getName()) {
            "int" => (int) $value,
            "float" => (float) $value,
            "bool" => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            "string" => is_bool($value) ? ($value ? "1" : "0") : (string) $value,
            default => $value,
        };
    }
}

