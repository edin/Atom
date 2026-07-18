<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class StatusDot implements ComponentInterface
{
    public AttributeBag $attributes;
    public string $variant = "neutral";
    public string $size = "md";
    public string $label = "";
    public string $class = "";

    public function render(): string
    {
        return Html::tag("span", Html::mergeAttributes([
            "class" => Html::classes("atom-status-dot", $this->class),
            "data-variant" => $this->variant,
            "data-size" => $this->size,
            "role" => $this->label !== "" ? "img" : null,
            "aria-label" => $this->label,
            "aria-hidden" => $this->label === "" ? "true" : null,
        ], $this->attributes->all()), "");
    }
}
