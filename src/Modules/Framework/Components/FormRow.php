<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class FormRow implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $columns = "auto";
    public string $gap = "md";
    public string $class = "";

    public function render(): string
    {
        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-form-row", $this->class),
            "data-columns" => $this->columns,
            "data-gap" => $this->gap,
        ], $this->attributes->all()), $this->content?->render() ?? "");
    }
}
