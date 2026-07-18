<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Divider implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $text = "";
    public string $orientation = "horizontal";
    public string $class = "";

    public function render(): string
    {
        $content = $this->content?->renderOr(Html::escape($this->text)) ?? Html::escape($this->text);

        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-divider", $this->class),
            "data-orientation" => $this->orientation,
            "role" => "separator",
            "aria-orientation" => $this->orientation,
        ], $this->attributes->all()), $content);
    }
}
