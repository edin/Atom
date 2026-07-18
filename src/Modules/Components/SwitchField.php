<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Html;

final class SwitchField extends FieldEntry
{
    public string $value = "1";
    public string $uncheckedValue = "0";

    public function render(): string
    {
        $label = $this->label === ""
            ? ""
            : Html::tag("span", ["class" => "atom-field-label"], Html::escape($this->label));

        $control = Html::voidTag("input", [
            "type" => "hidden",
            "name" => $this->name,
            "value" => $this->uncheckedValue,
        ]);
        $control .= $this->renderControl();

        $toggle = Html::tag("label", [
            "class" => "atom-switch-field",
            "for" => $this->fieldId(),
        ], $control . $label);

        return Html::tag("div", ["class" => "atom-field"], $toggle . $this->help() . $this->error());
    }

    protected function renderControl(): string
    {
        return Html::voidTag("input", Html::mergeAttributes([
            "type" => "checkbox",
            "role" => "switch",
            "id" => $this->fieldId(),
            "name" => $this->name,
            "value" => $this->value,
            "checked" => $this->isChecked(),
            "class" => Html::classes("atom-switch", $this->fieldClass()),
            "aria-invalid" => $this->hasError() ? "true" : null,
            "aria-describedby" => $this->describedBy(),
        ], $this->attributes->all()));
    }

    private function isChecked(): bool
    {
        return in_array(strtolower($this->fieldValue()), ["1", "true", "yes", "on"], true);
    }
}
