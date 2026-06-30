<?php

declare(strict_types=1);

namespace Atom\View\Render;

final readonly class ViewContext
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(private array $variables = [])
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function variables(): array
    {
        return $this->variables;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->variables);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->variables[$name] ?? $default;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function with(array $variables): self
    {
        return new self([...$this->variables, ...$variables]);
    }
}
