<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Atom\View\Html;

final readonly class AttributeBag
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(private array $attributes = [])
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function render(): string
    {
        $output = "";

        foreach ($this->attributes as $name => $value) {
            if ($value === false || $value === null) {
                continue;
            }

            if ($value === true) {
                $output .= " " . $name;
                continue;
            }

            $output .= " " . $name . '="' . Html::escape($value) . '"';
        }

        return $output;
    }
}
