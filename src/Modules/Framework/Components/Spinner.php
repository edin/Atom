<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class Spinner implements ComponentInterface
{
    public AttributeBag $attributes;
    public string $label = "Loading";
    public string $variant = "primary";
    public string $size = "md";
    public string $class = "";

    public function render(): string
    {
        $label = Html::tag("span", ["class" => "atom-visually-hidden"], Html::escape($this->label));

        return Html::tag("span", Html::mergeAttributes([
            "class" => Html::classes("atom-spinner", $this->class),
            "data-variant" => $this->variant,
            "data-size" => $this->size,
            "role" => "status",
        ], $this->attributes->all()), $label);
    }
}
