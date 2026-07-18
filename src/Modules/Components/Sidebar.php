<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Component\FromContext;
use Atom\View\Html;

final class Sidebar implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $footer = null;
    public AttributeBag $attributes;
    public string $brand = "";
    public string $href = "";
    public string $current = "";
    #[FromContext("currentPath")]
    public string $currentPath = "";
    public string $class = "";

    public function render(): string
    {
        $content = "";

        if ($this->brand !== "") {
            $brand = Html::escape($this->brand);
            if ($this->href !== "") {
                $brand = Html::tag("a", ["href" => $this->href, "class" => "atom-sidebar__brand-link"], $brand);
            }

            $content .= Html::tag("div", ["class" => "atom-sidebar__brand"], $brand);
        }

        $content .= Html::tag(
            "nav",
            ["class" => "atom-sidebar__nav", "aria-label" => "Sidebar"],
            $this->content?->render(["currentPath" => $this->resolvedCurrentPath()]) ?? ""
        );

        if ($this->footer !== null) {
            $content .= Html::tag("footer", ["class" => "atom-sidebar__footer"], $this->footer->render());
        }

        return Html::tag("section", Html::mergeAttributes([
            "class" => Html::classes("atom-sidebar", $this->class),
        ], $this->attributes->all()), $content);
    }

    private function resolvedCurrentPath(): string
    {
        if ($this->current === "auto") {
            return $this->currentPath;
        }

        return $this->current;
    }
}
