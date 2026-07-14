<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class ButtonGroup implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $orientation = "";
    public string $class = "";

    public function render(): string
    {
        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-button-group", $this->class),
            "data-orientation" => $this->orientation,
            "role" => "group",
        ], $this->attributes->all()), $this->content?->render() ?? "");
    }
}
