<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class SidebarGroup implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $label = "";
    public string $class = "";

    public function render(): string
    {
        $content = $this->label === ""
            ? ""
            : Html::tag("div", ["class" => "atom-sidebar-group__label"], Html::escape($this->label));

        $content .= Html::tag("div", ["class" => "atom-sidebar-group__items"], $this->content?->render() ?? "");

        return Html::tag("section", Html::mergeAttributes([
            "class" => Html::classes("atom-sidebar-group", $this->class),
        ], $this->attributes->all()), $content);
    }
}
