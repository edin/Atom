<?php

declare(strict_types=1);

namespace Atom\Di;

final readonly class TypeFactory
{
    private mixed $matcher;
    private mixed $factory;

    /**
     * @param callable(TypeInfo): bool $matcher
     * @param callable(string, Injector, InjectionContext): object $factory
     */
    private function __construct(callable $matcher, callable $factory)
    {
        $this->matcher = $matcher;
        $this->factory = $factory;
    }

    /**
     * @param callable(TypeInfo): bool $matcher
     * @param callable(string, Injector, InjectionContext): object $factory
     */
    public static function match(callable $matcher, callable $factory): self
    {
        return new self($matcher, $factory);
    }

    public function matches(TypeInfo $type): bool
    {
        return (bool) ($this->matcher)($type);
    }

    public function create(string $className, Injector $injector, InjectionContext $context): object
    {
        return ($this->factory)($className, $injector, $context);
    }
}
