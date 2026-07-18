<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;
use Atom\Support\Paths;

final class Stats implements ComponentInterface
{
    public Fragment|string|null $icon = null;
    public AttributeBag $attributes;
    public string $label = "";
    public string $value = "";
    public string $description = "";
    public string $trend = "";
    public string $href = "";
    public string $class = "";

    public function __construct(private ?Paths $paths = null)
    {
    }

    public function render(): string
    {
        $content = Html::tag("div", ["class" => "atom-stats__main"], $this->main());
        $icon = $this->icon();
        if ($icon !== "") {
            $content .= Html::tag("div", ["class" => "atom-stats__icon"], $icon);
        }

        $tag = $this->href === "" ? "article" : "a";

        return Html::tag($tag, Html::mergeAttributes([
            "href" => $tag === "a" ? $this->href : null,
            "class" => Html::classes("atom-stats", $this->class),
        ], $this->attributes->all()), $content);
    }

    private function icon(): string
    {
        if ($this->icon instanceof Fragment) {
            return $this->icon->render();
        }

        if ($this->icon === null || $this->icon === "") {
            return "";
        }

        return Icon::from($this->icon, $this->paths)->render();
    }

    private function main(): string
    {
        $content = "";

        if ($this->label !== "") {
            $content .= Html::tag("div", ["class" => "atom-stats__label"], Html::escape($this->label));
        }

        if ($this->value !== "") {
            $content .= Html::tag("div", ["class" => "atom-stats__value"], Html::escape($this->value));
        }

        if ($this->description !== "" || $this->trend !== "") {
            $content .= Html::tag("div", ["class" => "atom-stats__meta"], $this->meta());
        }

        return $content;
    }

    private function meta(): string
    {
        $content = "";

        if ($this->description !== "") {
            $content .= Html::tag("span", ["class" => "atom-stats__description"], Html::escape($this->description));
        }

        if ($this->trend !== "") {
            $content .= Html::tag("span", ["class" => "atom-stats__trend"], Html::escape($this->trend));
        }

        return $content;
    }
}
