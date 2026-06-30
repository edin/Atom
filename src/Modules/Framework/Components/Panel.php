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
    public AttributeBag $attributes;
    public string $title = "";
    public string $class = "";

    public function render(): string
    {
        $content = "";
        if ($this->title !== "") {
            $content .= Html::tag("header", ["class" => "atom-panel__header"], Html::tag(
                "h2",
                ["class" => "atom-panel__title"],
                Html::escape($this->title)
            ));
        }

        $content .= Html::tag("div", ["class" => "atom-panel__body"], $this->content?->render() ?? "");

        return Html::tag("section", Html::mergeAttributes([
            "class" => Html::classes("atom-panel", $this->class),
        ], $this->attributes->all()), $content);
    }
}
