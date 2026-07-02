<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class AppShell implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $sidebar = null;
    public ?Fragment $header = null;
    public AttributeBag $attributes;
    public string $title = "";
    public string $class = "";

    public function render(): string
    {
        $content = "";

        if ($this->sidebar !== null) {
            $content .= Html::tag("aside", ["class" => "atom-app-shell__sidebar"], $this->sidebar->render());
        }

        $main = "";
        if ($this->header !== null || $this->title !== "") {
            $main .= Html::tag("header", ["class" => "atom-app-shell__header"], $this->header());
        }

        $main .= Html::tag("main", ["class" => "atom-app-shell__main"], $this->content?->render() ?? "");
        $content .= Html::tag("div", ["class" => "atom-app-shell__content"], $main);

        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-app-shell", $this->class),
        ], $this->attributes->all()), $content);
    }

    private function header(): string
    {
        if ($this->header !== null) {
            return $this->header->render();
        }

        return Html::tag("h1", ["class" => "atom-app-shell__title"], Html::escape($this->title));
    }
}
