<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Page\Page;
use Atom\View\Component\AttributeBag;

trait FieldComponent
{
    public Page $page;
    public string $name;
    public ?string $id = null;
    public ?string $value = null;
    public string $class = "";
    public string $invalidClass = "is-invalid";
    public AttributeBag $attributes;

    private function fieldId(): string
    {
        return $this->id ?? str_replace([".", "[", "]"], "-", $this->name);
    }

    private function fieldValue(): string
    {
        if ($this->value !== null) {
            return $this->value;
        }

        if (!property_exists($this->page, $this->name)) {
            return "";
        }

        $value = $this->page->{$this->name};

        return is_scalar($value) || $value === null ? (string) $value : "";
    }

    private function fieldClass(): string
    {
        $classes = trim($this->class);
        if ($this->hasError()) {
            $classes = trim($classes . " " . $this->invalidClass);
        }

        return $classes;
    }

    private function hasError(): bool
    {
        return $this->page->errors()->has($this->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function extraAttributes(): array
    {
        return isset($this->attributes) ? $this->attributes->all() : [];
    }
}
