<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Support\Paths;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class ListItem implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public Fragment|string|null $icon = null;
    public AttributeBag $attributes;
    public string $title = "";
    public string $description = "";
    public string $href = "";
    public string $class = "";

    public function __construct(private ?Paths $paths = null)
    {
    }

    public function render(): string
    {
        $content = $this->iconHtml() . Html::tag("div", ["class" => "atom-list-item__main"], $this->main());

        if ($this->actions !== null) {
            $content .= Html::tag("div", ["class" => "atom-list-item__actions"], $this->actions->render());
        }

        return Html::tag("li", Html::mergeAttributes([
            "class" => Html::classes("atom-list-item", $this->class),
        ], $this->attributes->all()), $content);
    }

    private function main(): string
    {
        $content = "";

        if ($this->title !== "") {
            $title = Html::escape($this->title);
            if ($this->href !== "") {
                $title = Html::tag("a", ["href" => $this->href, "class" => "atom-list-item__link"], $title);
            }

            $content .= Html::tag("div", ["class" => "atom-list-item__title"], $title);
        }

        if ($this->description !== "") {
            $content .= Html::tag("div", ["class" => "atom-list-item__description"], Html::escape($this->description));
        }

        if ($this->content !== null) {
            $content .= Html::tag("div", ["class" => "atom-list-item__content"], $this->content->render());
        }

        return $content;
    }

    private function iconHtml(): string
    {
        if ($this->icon instanceof Fragment) {
            return Html::tag("div", ["class" => "atom-list-item__icon"], $this->icon->render());
        }

        if ($this->icon === null || $this->icon === "") {
            return "";
        }

        return Html::tag("div", ["class" => "atom-list-item__icon"], Icon::from($this->icon, $this->paths)->render());
    }
}
