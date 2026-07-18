<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Html;

final class TextField extends FieldEntry
{
    public string $type = "text";
    public ?string $value = null;

    protected function renderControl(): string
    {
        return Html::voidTag("input", Html::mergeAttributes([
            "type" => $this->type,
            "id" => $this->fieldId(),
            "name" => $this->name,
            "value" => $this->fieldValue(),
            "class" => Html::classes("atom-input", $this->fieldClass()),
            "aria-invalid" => $this->hasError() ? "true" : null,
            "aria-describedby" => $this->describedBy(),
        ], $this->attributes->all()));
    }

    protected function fieldValue(): string
    {
        if ($this->value !== null) {
            return $this->value;
        }

        return parent::fieldValue();
    }
}
