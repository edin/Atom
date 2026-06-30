<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class TextInput implements ComponentInterface
{
    use FieldComponent;

    public string $type = "text";

    public function render(): string
    {
        return Html::voidTag("input", Html::mergeAttributes([
            "type" => $this->type,
            "id" => $this->fieldId(),
            "name" => $this->name,
            "value" => $this->fieldValue(),
            "class" => $this->fieldClass(),
            "aria-invalid" => $this->hasError() ? "true" : null,
            "aria-describedby" => $this->hasError() ? $this->fieldId() . "-error" : null,
        ], $this->extraAttributes()));
    }
}
