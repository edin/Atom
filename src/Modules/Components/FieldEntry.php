<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Page\Page;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\FromContext;
use Atom\View\Html;

abstract class FieldEntry implements ComponentInterface
{
    public Page $page;
    public AttributeBag $attributes;
    public string $name;
    public string $label = "";
    public ?string $id = null;
    public ?string $bind = null;
    public string $help = "";
    public string $class = "";
    public string $invalidClass = "is-invalid";
    #[FromContext("model")]
    public mixed $model = null;

    public function render(): string
    {
        return $this->field($this->renderControl() . $this->help() . $this->error());
    }

    abstract protected function renderControl(): string;

    protected function field(string $content): string
    {
        $label = $this->label === "" ? "" : Html::tag("span", ["class" => "atom-field-label"], Html::escape($this->label));

        return Html::tag("label", [
            "class" => "atom-field",
            "for" => $this->fieldId(),
        ], $label . $content);
    }

    protected function error(): string
    {
        $message = $this->page->errors()->first($this->boundName());

        return $message === null
            ? ""
            : Html::tag("p", ["id" => $this->fieldId() . "-error", "class" => "atom-field-error"], Html::escape($message));
    }

    protected function help(): string
    {
        return $this->help === ""
            ? ""
            : Html::tag("p", ["id" => $this->fieldId() . "-help", "class" => "atom-field-help"], Html::escape($this->help));
    }

    protected function fieldId(): string
    {
        return $this->id ?? str_replace([".", "[", "]"], "-", $this->name);
    }

    protected function fieldValue(): string
    {
        $property = $this->boundName();
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

    protected function fieldClass(): string
    {
        return Html::classes($this->class, [$this->invalidClass => $this->hasError()]);
    }

    protected function hasError(): bool
    {
        return $this->page->errors()->has($this->boundName());
    }

    protected function describedBy(): ?string
    {
        $ids = [];

        if ($this->help !== "") {
            $ids[] = $this->fieldId() . "-help";
        }

        if ($this->hasError()) {
            $ids[] = $this->fieldId() . "-error";
        }

        return $ids === [] ? null : implode(" ", $ids);
    }

    protected function boundName(): string
    {
        return $this->bind ?? $this->name;
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
