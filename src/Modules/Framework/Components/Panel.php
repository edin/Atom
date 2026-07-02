<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Panel implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public ?Fragment $footer = null;
    public AttributeBag $attributes;
    public string $title = "";
    public string $description = "";
    public string $class = "";
    public string $padding = "";

    public function render(): string
    {
        $content = "";
        if ($this->title !== "" || $this->description !== "" || $this->actions !== null) {
            $content .= Html::tag("header", ["class" => "atom-panel__header"], $this->header());
        }

        $content .= Html::tag("div", ["class" => "atom-panel__body"], $this->content?->render() ?? "");

        if ($this->footer !== null) {
            $content .= Html::tag("footer", ["class" => "atom-panel__footer"], $this->footer->render());
        }

        return Html::tag("section", Html::mergeAttributes([
            "class" => Html::classes("atom-panel", $this->class),
            "data-padding" => $this->padding,
        ], $this->attributes->all()), $content);
    }

    private function header(): string
    {
        $main = "";

        if ($this->title !== "") {
            $main .= Html::tag("h2", ["class" => "atom-panel__title"], Html::escape($this->title));
        }

        if ($this->description !== "") {
            $main .= Html::tag("p", ["class" => "atom-panel__description"], Html::escape($this->description));
        }

        $content = Html::tag("div", ["class" => "atom-panel__main"], $main);

        if ($this->actions !== null) {
            $content .= Html::tag("div", ["class" => "atom-panel__actions"], $this->actions->render());
        }

        return $content;
    }
}
