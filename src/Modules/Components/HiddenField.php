<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Page\Page;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\FromContext;
use Atom\View\Html;

final class HiddenField implements ComponentInterface
{
    public Page $page;
    public AttributeBag $attributes;
    public string $name;
    public ?string $bind = null;
    public ?string $value = null;
    #[FromContext("model")]
    public mixed $model = null;

    public function render(): string
    {
        return Html::voidTag("input", Html::mergeAttributes([
            "type" => "hidden",
            "name" => $this->name,
            "value" => $this->fieldValue(),
        ], $this->attributes->all()));
    }

    private function fieldValue(): string
    {
        if ($this->value !== null) {
            return $this->value;
        }

        $property = $this->bind ?? $this->name;
        if ($this->model !== null && $this->hasModelValue($property)) {
            $value = $this->modelValue($property);

            return is_scalar($value) || $value === null ? (string) $value : "";
        }

        if (!property_exists($this->page, $property)) {
            return "";
        }

        $value = $this->page->{$property};

        return is_scalar($value) || $value === null ? (string) $value : "";
    }

    private function hasModelValue(string $property): bool
    {
        if (is_array($this->model)) {
            return array_key_exists($property, $this->model);
        }

        return is_object($this->model) && isset($this->model->{$property});
    }

    private function modelValue(string $property): mixed
    {
        return is_array($this->model) ? $this->model[$property] : $this->model->{$property};
    }
}
