<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Toast implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public AttributeBag $attributes;
    public bool $show = true;
    public string $title = "";
    public string $description = "";
    public string $text = "";
    public string $variant = "neutral";
    public string $position = "top-end";
    public string $role = "status";
    public string $class = "";

    public function render(): string
    {
        if (!$this->show) {
            return "";
        }

        return Html::tag("div", [
            "class" => "atom-toast-region",
            "data-position" => $this->position,
        ], Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-toast", $this->class),
            "data-variant" => $this->variant,
            "role" => $this->role,
        ], $this->attributes->all()), $this->body()));
    }

    private function body(): string
    {
        $content = Html::tag("div", ["class" => "atom-toast__content"], $this->contentHtml());

        if ($this->actions !== null) {
            $content .= Html::tag("div", ["class" => "atom-toast__actions"], $this->actions->render());
        }

        return $content;
    }

    private function contentHtml(): string
    {
        $content = "";

        if ($this->title !== "") {
            $content .= Html::tag("strong", ["class" => "atom-toast__title"], Html::escape($this->title));
        }

        if ($this->description !== "") {
            $content .= Html::tag("p", ["class" => "atom-toast__description"], Html::escape($this->description));
        }

        if ($this->content !== null || $this->text !== "") {
            $content .= Html::tag("div", ["class" => "atom-toast__body"], $this->content());
        }

        return $content;
    }

    private function content(): string
    {
        if ($this->content !== null) {
            return $this->content->renderOr(Html::escape($this->text));
        }

        return Html::escape($this->text);
    }
}
