<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Support\Paths;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Alert implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public AttributeBag $attributes;
    public string $title = "";
    public string $description = "";
    public string $text = "";
    public string $variant = "neutral";
    public string $appearance = "soft";
    public string $size = "";
    public string $icon = "";
    public string $role = "status";
    public string $class = "";

    public function __construct(private ?Paths $paths = null)
    {
    }

    public function render(): string
    {
        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-alert", $this->class),
            "data-variant" => $this->variant,
            "data-appearance" => $this->appearance,
            "data-size" => $this->size,
            "role" => $this->role,
        ], $this->attributes->all()), $this->body());
    }

    private function body(): string
    {
        if ($this->title === "" && $this->description === "" && $this->actions === null && $this->icon === "") {
            return $this->content();
        }

        $content = $this->renderIcon();
        $content .= Html::tag("div", ["class" => "atom-alert__content"], $this->structuredContent());

        if ($this->actions !== null) {
            $content .= Html::tag("div", ["class" => "atom-alert__actions"], $this->actions->render());
        }

        return $content;
    }

    private function structuredContent(): string
    {
        $content = "";

        if ($this->title !== "") {
            $content .= Html::tag("strong", ["class" => "atom-alert__title"], Html::escape($this->title));
        }

        if ($this->description !== "") {
            $content .= Html::tag("p", ["class" => "atom-alert__description"], Html::escape($this->description));
        }

        if ($this->content !== null || $this->text !== "") {
            $content .= Html::tag("div", ["class" => "atom-alert__body"], $this->content());
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

    private function renderIcon(): string
    {
        if ($this->icon === "") {
            return "";
        }

        $icon = Icon::from($this->icon, $this->paths);
        $icon->variant = $this->variant === "neutral" ? "" : $this->variant;

        return Html::tag("span", ["class" => "atom-alert__icon"], $icon->render());
    }

}
