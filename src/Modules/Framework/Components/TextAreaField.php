<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Html;

final class TextAreaField extends FieldEntry
{
    public ?string $value = null;

    protected function renderControl(): string
    {
        return Html::tag("textarea", Html::mergeAttributes([
            "id" => $this->fieldId(),
            "name" => $this->name,
            "class" => Html::classes("atom-textarea", $this->fieldClass()),
            "aria-invalid" => $this->hasError() ? "true" : null,
            "aria-describedby" => $this->hasError() ? $this->fieldId() . "-error" : null,
        ], $this->attributes->all()), Html::escape($this->fieldValue()));
    }

    protected function fieldValue(): string
    {
        if ($this->value !== null) {
            return $this->value;
        }

        return parent::fieldValue();
    }
}
