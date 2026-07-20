<?php

declare(strict_types=1);

namespace Atom\View\Component;

/**
 * @implements \IteratorAggregate<string, class-string<ComponentInterface>>
 */
final readonly class ComponentSet implements \Countable, \IteratorAggregate
{
    /** @var array<string, class-string<ComponentInterface>> */
    private array $components;

    /**
     * @param array<string, class-string<ComponentInterface>> $components
     */
    private function __construct(array $components)
    {
        $validated = [];
        foreach ($components as $name => $className) {
            $name = self::validName($name);
            self::assertComponent($name, $className);

            if (isset($validated[$name])) {
                throw new ComponentRegistryException("Component '{$name}' is defined more than once in the set.");
            }

            $validated[$name] = $className;
        }

        $this->components = $validated;
    }

    /**
     * @param array<string, class-string<ComponentInterface>> $components
     */
    public static function from(array $components = []): self
    {
        return new self($components);
    }

    /**
     * @param class-string<ComponentInterface> $className
     */
    public function with(string $name, string $className): self
    {
        $name = self::validName($name);
        if (isset($this->components[$name])) {
            throw new ComponentRegistryException("Component '{$name}' is already defined in the set.");
        }

        return new self([...$this->components, $name => $className]);
    }

    public function merge(self ...$sets): self
    {
        $components = $this->components;
        foreach ($sets as $set) {
            foreach ($set as $name => $className) {
                if (isset($components[$name])) {
                    throw new ComponentRegistryException(
                        "Component '{$name}' is defined by more than one component set."
                    );
                }
                $components[$name] = $className;
            }
        }

        return new self($components);
    }

    /** @return array<string, class-string<ComponentInterface>> */
    public function all(): array
    {
        return $this->components;
    }

    public function has(string $name): bool
    {
        return isset($this->components[trim($name)]);
    }

    /** @return class-string<ComponentInterface> */
    public function get(string $name): string
    {
        $name = trim($name);

        return $this->components[$name]
            ?? throw new ComponentRegistryException("Component '{$name}' is not defined in the set.");
    }

    public function count(): int
    {
        return count($this->components);
    }

    /** @return \Traversable<string, class-string<ComponentInterface>> */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->components);
    }

    private static function validName(string $name): string
    {
        $name = trim($name);
        if ($name === "") {
            throw new ComponentRegistryException("Component name cannot be empty.");
        }

        return $name;
    }

    /** @param class-string<ComponentInterface> $className */
    private static function assertComponent(string $name, string $className): void
    {
        if (!is_a($className, ComponentInterface::class, true)) {
            throw new ComponentRegistryException(
                "Component '{$name}' must implement " . ComponentInterface::class . "."
            );
        }
    }
}
