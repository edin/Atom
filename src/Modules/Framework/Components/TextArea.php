<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class TextArea implements ComponentInterface
{
    use FieldComponent;

    public function render(): string
    {
        return Html::tag("textarea", Html::mergeAttributes([
            "id" => $this->fieldId(),
            "name" => $this->name,
            "class" => $this->fieldClass(),
            "aria-invalid" => $this->hasError() ? "true" : null,
            "aria-describedby" => $this->hasError() ? $this->fieldId() . "-error" : null,
        ], $this->extraAttributes()), Html::escape($this->fieldValue()));
    }
}
