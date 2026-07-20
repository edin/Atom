<?php

declare(strict_types=1);

namespace Atom\View\Component;

final class ComponentRegistry
{
    /** @var array<string, class-string<ComponentInterface>> */
    private array $components = [];

    /**
     * @param class-string<ComponentInterface> $className
     */
    public function register(string $name, string $className): self
    {
        $name = trim($name);

        if ($name === "") {
            throw new ComponentRegistryException("Component name cannot be empty.");
        }

        if (isset($this->components[$name])) {
            throw new ComponentRegistryException("Component '{$name}' is already registered.");
        }

        if (!is_a($className, ComponentInterface::class, true)) {
            throw new ComponentRegistryException("Component '{$name}' must implement " . ComponentInterface::class . ".");
        }

        $this->components[$name] = $className;

        return $this;
    }

    public function import(ComponentSet $set): self
    {
        foreach ($set as $name => $_className) {
            if (isset($this->components[$name])) {
                throw new ComponentRegistryException("Component '{$name}' is already registered.");
            }
        }

        foreach ($set as $name => $className) {
            $this->components[$name] = $className;
        }

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->components[$name]);
    }

    /**
     * @return class-string<ComponentInterface>
     */
    public function get(string $name): string
    {
        return $this->components[$name]
            ?? throw new ComponentRegistryException("Component '{$name}' is not registered.");
    }

    /**
     * @return array<string, class-string<ComponentInterface>>
     */
    public function all(): array
    {
        return $this->components;
    }
}
