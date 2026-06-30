<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Page\Page;
use Atom\View\Component\AttributeBag;
use Atom\View\Html;

trait FieldComponent
{
    public Page $page;
    public string $name;
    public ?string $id = null;
    public ?string $value = null;
    public string $class = "";
    public string $invalidClass = "is-invalid";
    public AttributeBag $attributes;

    public function __construct()
    {
        $this->attributes = new AttributeBag();
    }

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
     * @param array<string, mixed> $attributes
     */
    private function renderAttributes(array $attributes): string
    {
        $html = "";

        foreach ($attributes as $name => $value) {
            if ($value === false || $value === null || $value === "") {
                continue;
            }

            if ($value === true) {
                $html .= " " . $name;
                continue;
            }

            $html .= " " . $name . '="' . Html::escape($value) . '"';
        }

        return $html;
    }

    private function extraAttributes(): string
    {
        return isset($this->attributes) ? $this->attributes->render() : "";
    }
}
