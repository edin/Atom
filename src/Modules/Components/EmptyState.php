<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class EmptyState implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public AttributeBag $attributes;
    public string $title = "";
    public string $description = "";
    public string $class = "";

    public function render(): string
    {
        $content = "";

        if ($this->title !== "") {
            $content .= Html::tag("h2", ["class" => "atom-empty-state__title"], Html::escape($this->title));
        }

        if ($this->description !== "") {
            $content .= Html::tag("p", ["class" => "atom-empty-state__description"], Html::escape($this->description));
        }

        $content .= $this->content?->render() ?? "";

        if ($this->actions !== null) {
            $content .= Html::tag("div", ["class" => "atom-empty-state__actions"], $this->actions->render());
        }

        return Html::tag("section", Html::mergeAttributes([
            "class" => Html::classes("atom-empty-state", $this->class),
        ], $this->attributes->all()), $content);
    }
}
