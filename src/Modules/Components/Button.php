<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Support\Paths;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Button implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $text = "";
    public string $type = "button";
    public ?string $href = null;
    public string $variant = "";
    public string $appearance = "";
    public string $size = "";
    public string $shape = "";
    public string $icon = "";
    public string $iconRight = "";
    public bool $disabled = false;
    public bool $loading = false;
    public string $class = "";

    public function __construct(private ?Paths $paths = null)
    {
    }

    public function render(): string
    {
        $tag = $this->href === null ? "button" : "a";
        $disabled = $this->disabled || $this->loading;
        $attributes = Html::mergeAttributes([
            "href" => $tag === "a" && !$disabled ? $this->href : null,
            "type" => $tag === "button" ? $this->type : null,
            "class" => Html::classes("atom-button", $this->class),
            "data-variant" => $this->variant,
            "data-appearance" => $this->appearance,
            "data-size" => $this->size,
            "data-shape" => $this->shape,
            "data-loading" => $this->loading ? "true" : null,
            "disabled" => $tag === "button" && $disabled ? true : null,
            "aria-disabled" => $tag === "a" && $disabled ? "true" : null,
            "aria-busy" => $this->loading ? "true" : null,
            "tabindex" => $tag === "a" && $disabled ? "-1" : null,
        ], $this->attributes->all());

        return Html::tag($tag, $attributes, $this->content());
    }

    private function content(): string
    {
        $content = "";

        if ($this->loading) {
            $content .= Html::tag("span", ["class" => "atom-button__spinner", "aria-hidden" => "true"]);
        } elseif ($this->icon !== "") {
            $content .= $this->renderIcon($this->icon);
        }

        if ($this->content !== null) {
            $content .= $this->content->renderOr(Html::escape($this->text));
        } else {
            $content .= Html::escape($this->text);
        }

        if (!$this->loading && $this->iconRight !== "") {
            $content .= $this->renderIcon($this->iconRight);
        }

        return $content;
    }

    private function renderIcon(string $icon): string
    {
        return Icon::from($icon, $this->paths)->render();
    }

}
