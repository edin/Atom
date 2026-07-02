<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Support\Paths;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class SidebarItem implements ComponentInterface
{
    public ?Fragment $content = null;
    public Fragment|string|null $icon = null;
    public AttributeBag $attributes;
    public string $href = "";
    public string $label = "";
    public bool $active = false;
    public string $class = "";

    public function __construct(private ?Paths $paths = null)
    {
    }

    public function render(): string
    {
        $content = $this->iconHtml();
        $content .= Html::tag("span", ["class" => "atom-sidebar-item__label"], $this->label());

        $tag = $this->href === "" ? "span" : "a";

        return Html::tag($tag, Html::mergeAttributes([
            "href" => $tag === "a" ? $this->href : null,
            "class" => Html::classes("atom-sidebar-item", ["is-active" => $this->active], $this->class),
            "aria-current" => $this->active && $tag === "a" ? "page" : null,
        ], $this->attributes->all()), $content);
    }

    private function label(): string
    {
        if ($this->content !== null) {
            return $this->content->renderOr(Html::escape($this->label));
        }

        return Html::escape($this->label);
    }

    private function iconHtml(): string
    {
        if ($this->icon instanceof Fragment) {
            return Html::tag("span", ["class" => "atom-sidebar-item__icon"], $this->icon->render());
        }

        if ($this->icon === null || $this->icon === "") {
            return "";
        }

        return Html::tag("span", ["class" => "atom-sidebar-item__icon"], Icon::from($this->icon, $this->paths)->render());
    }
}
