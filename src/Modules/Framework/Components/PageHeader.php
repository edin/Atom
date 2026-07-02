<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class PageHeader implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public AttributeBag $attributes;
    public string $title = "";
    public string $description = "";
    public string $class = "";

    public function render(): string
    {
        $main = Html::tag("div", ["class" => "atom-page-header__main"], $this->mainContent());
        $actions = $this->actions === null
            ? ""
            : Html::tag("div", ["class" => "atom-page-header__actions"], $this->actions->render());

        return Html::tag("header", Html::mergeAttributes([
            "class" => Html::classes("atom-page-header", $this->class),
        ], $this->attributes->all()), $main . $actions);
    }

    private function mainContent(): string
    {
        $content = "";

        if ($this->title !== "") {
            $content .= Html::tag("h1", ["class" => "atom-page-header__title"], Html::escape($this->title));
        }

        if ($this->description !== "") {
            $content .= Html::tag("p", ["class" => "atom-page-header__description"], Html::escape($this->description));
        }

        return $content . ($this->content?->render() ?? "");
    }
}
