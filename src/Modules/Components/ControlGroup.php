<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class ControlGroup implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $label = "";
    public bool $spacer = true;
    public string $class = "";

    public function render(): string
    {
        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-control-group", $this->class),
        ], $this->attributes->all()), $this->label() . Html::tag(
            "div",
            ["class" => "atom-control-group__controls"],
            $this->content?->render() ?? ""
        ));
    }

    private function label(): string
    {
        if ($this->label !== "") {
            return Html::tag("span", ["class" => "atom-field-label atom-control-group__label"], Html::escape($this->label));
        }

        if (!$this->spacer) {
            return "";
        }

        return Html::tag("span", ["class" => "atom-field-label atom-control-group__label", "aria-hidden" => "true"], "&nbsp;");
    }
}
