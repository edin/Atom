<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Html;

final class SelectField extends FieldEntry
{
    /** @var iterable<mixed> */
    public iterable $options = [];
    public string $optionValue = "value";
    public string $optionText = "text";
    public ?string $value = null;

    protected function renderControl(): string
    {
        return Html::tag("select", Html::mergeAttributes([
            "id" => $this->fieldId(),
            "name" => $this->name,
            "class" => Html::classes("atom-select", $this->fieldClass()),
            "aria-invalid" => $this->hasError() ? "true" : null,
            "aria-describedby" => $this->hasError() ? $this->fieldId() . "-error" : null,
        ], $this->attributes->all()), $this->optionsHtml());
    }

    private function optionsHtml(): string
    {
        $html = "";
        $selected = $this->fieldValue();

        foreach ($this->options as $key => $option) {
            $value = $this->optionPart($option, $this->optionValue, $key);
            $text = $this->optionPart($option, $this->optionText, $value);

            $html .= Html::tag("option", [
                "value" => $value,
                "selected" => (string) $value === $selected,
            ], Html::escape($text));
        }

        return $html;
    }

    private function optionPart(mixed $option, string $name, mixed $fallback): mixed
    {
        if (is_array($option) && array_key_exists($name, $option)) {
            return $option[$name];
        }

        if (is_object($option) && isset($option->{$name})) {
            return $option->{$name};
        }

        if (is_scalar($option) || $option === null) {
            return $option;
        }

        return $fallback;
    }

    protected function fieldValue(): string
    {
        if ($this->value !== null) {
            return $this->value;
        }

        return parent::fieldValue();
    }
}
