<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Tab implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $name = "";
    public string $label = "";
    public string $text = "";
    public string $href = "";
    public string $class = "";

    public function render(): string
    {
        return $this->renderContent();
    }

    public function renderHeader(bool $active): string
    {
        $tag = $this->href === "" ? "button" : "a";

        return Html::tag($tag, Html::mergeAttributes([
            "href" => $this->href,
            "type" => $tag === "button" ? "button" : null,
            "class" => Html::classes("atom-tab", ["is-active" => $active], $this->class),
            "aria-current" => $active ? "page" : null,
            "data-tab" => $this->name,
        ], $this->attributes->all()), Html::escape($this->headerText()));
    }

    public function renderContent(): string
    {
        if ($this->content !== null) {
            return $this->content->render();
        }

        return Html::escape($this->text);
    }

    private function headerText(): string
    {
        if ($this->label !== "") {
            return $this->label;
        }

        if ($this->text !== "") {
            return $this->text;
        }

        return $this->name;
    }
}
