<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Card implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public AttributeBag $attributes;
    public string $title = "";
    public string $description = "";
    public ?string $href = null;
    public string $class = "";

    public function render(): string
    {
        $content = "";

        if ($this->title !== "" || $this->description !== "") {
            $content .= Html::tag("header", ["class" => "atom-card__header"], $this->header());
        }

        if ($this->content !== null) {
            $content .= Html::tag("div", ["class" => "atom-card__body"], $this->content->render());
        }

        if ($this->actions !== null) {
            $content .= Html::tag("footer", ["class" => "atom-card__actions"], $this->actions->render());
        }

        return Html::tag("article", Html::mergeAttributes([
            "class" => Html::classes("atom-card", $this->class),
        ], $this->attributes->all()), $content);
    }

    private function header(): string
    {
        $content = "";

        if ($this->title !== "") {
            $title = Html::escape($this->title);
            if ($this->href !== null) {
                $title = Html::tag("a", ["href" => $this->href, "class" => "atom-card__link"], $title);
            }

            $content .= Html::tag("h3", ["class" => "atom-card__title"], $title);
        }

        if ($this->description !== "") {
            $content .= Html::tag("p", ["class" => "atom-card__description"], Html::escape($this->description));
        }

        return $content;
    }
}
