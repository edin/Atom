<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Inline implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $gap = "";
    public string $align = "";
    public string $justify = "";
    public string $class = "";

    public function render(): string
    {
        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-inline", $this->class),
            "data-gap" => $this->gap,
            "data-align" => $this->align,
            "data-justify" => $this->justify,
        ], $this->attributes->all()), $this->content?->render() ?? "");
    }
}
