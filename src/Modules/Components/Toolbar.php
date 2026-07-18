<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Toolbar implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $start = null;
    public ?Fragment $end = null;
    public AttributeBag $attributes;
    public string $gap = "";
    public string $align = "center";
    public string $justify = "between";
    public string $appearance = "";
    public string $class = "";

    public function render(): string
    {
        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-toolbar", $this->class),
            "data-gap" => $this->gap,
            "data-align" => $this->align,
            "data-justify" => $this->justify,
            "data-appearance" => $this->appearance,
        ], $this->attributes->all()), $this->content());
    }

    private function content(): string
    {
        if ($this->start === null && $this->end === null) {
            return $this->content?->render() ?? "";
        }

        return Html::tag("div", ["class" => "atom-toolbar__section atom-toolbar__section--start"], $this->start?->render() ?? "")
            . Html::tag("div", ["class" => "atom-toolbar__section atom-toolbar__section--end"], $this->end?->render() ?? "");
    }
}
