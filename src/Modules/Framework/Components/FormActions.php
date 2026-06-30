<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class FormActions implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $align = "start";
    public string $class = "";

    public function render(): string
    {
        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-form-actions", $this->class),
            "data-align" => $this->align,
        ], $this->attributes->all()), $this->content?->render() ?? "");
    }
}
