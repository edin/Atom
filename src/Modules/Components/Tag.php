<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Tag implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $text = "";
    public string $variant = "neutral";
    public string $size = "md";
    public string $class = "";

    public function render(): string
    {
        $content = $this->content?->renderOr(Html::escape($this->text)) ?? Html::escape($this->text);

        return Html::tag("span", Html::mergeAttributes([
            "class" => Html::classes("atom-tag", $this->class),
            "data-variant" => $this->variant,
            "data-size" => $this->size,
        ], $this->attributes->all()), $content);
    }
}
