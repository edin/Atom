<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Html;

final class RadioField extends FieldEntry
{
    /** @var iterable<mixed> */
    public iterable $options = [];
    public string $optionValue = "value";
    public string $optionText = "text";
    public ?string $value = null;
    public string $orientation = "vertical";

    public function render(): string
    {
        $legend = $this->label === ""
            ? ""
            : Html::tag("legend", ["class" => "atom-field-label"], Html::escape($this->label));

        $content = $legend;
        $content .= Html::tag("div", [
            "class" => "atom-radio-field__options",
            "data-orientation" => $this->orientation,
        ], $this->optionsHtml());
        $content .= $this->help() . $this->error();

        return Html::tag("fieldset", [
            "class" => "atom-field atom-radio-field",
            "aria-describedby" => $this->describedBy(),
        ], $content);
    }

    protected function renderControl(): string
    {
        return $this->optionsHtml();
    }

    private function optionsHtml(): string
    {
        $html = "";
        $selected = $this->fieldValue();
        $index = 0;

        foreach ($this->options as $key => $option) {
            $value = $this->optionPart($option, $this->optionValue, $key);
            $text = $this->optionPart($option, $this->optionText, $value);
            $id = $this->fieldId() . "-" . $index++;

            $input = Html::voidTag("input", Html::mergeAttributes([
                "type" => "radio",
                "id" => $id,
                "name" => $this->name,
                "value" => $value,
                "checked" => (string) $value === $selected,
                "class" => Html::classes("atom-radio", $this->fieldClass()),
                "aria-invalid" => $this->hasError() ? "true" : null,
                "aria-describedby" => $this->describedBy(),
            ], $this->attributes->all()));

            $html .= Html::tag("label", [
                "class" => "atom-radio-option",
                "for" => $id,
            ], $input . Html::tag("span", [], Html::escape($text)));
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
        return $this->value ?? parent::fieldValue();
    }
}
