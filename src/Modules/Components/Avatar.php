<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class Avatar implements ComponentInterface
{
    public AttributeBag $attributes;
    public string $src = "";
    public string $alt = "";
    public string $name = "";
    public string $initials = "";
    public string $size = "md";
    public string $shape = "circle";
    public string $class = "";

    public function render(): string
    {
        $attributes = [
            "class" => Html::classes("atom-avatar", $this->class),
            "data-size" => $this->size,
            "data-shape" => $this->shape,
        ];

        if ($this->src !== "") {
            $content = Html::voidTag("img", [
                "class" => "atom-avatar__image",
                "src" => $this->src,
                "alt" => $this->alt !== "" ? $this->alt : $this->name,
            ]);
        } else {
            $label = $this->alt !== "" ? $this->alt : $this->name;
            $attributes["role"] = "img";
            $attributes["aria-label"] = $label !== "" ? $label : null;
            $attributes["aria-hidden"] = $label === "" ? "true" : null;
            $content = Html::tag("span", ["class" => "atom-avatar__initials"], Html::escape($this->fallbackInitials()));
        }

        return Html::tag("span", Html::mergeAttributes($attributes, $this->attributes->all()), $content);
    }

    private function fallbackInitials(): string
    {
        if ($this->initials !== "") {
            return strtoupper(substr(trim($this->initials), 0, 2));
        }

        $parts = preg_split('/\s+/', trim($this->name), flags: PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return "?";
        }

        $initials = substr($parts[0], 0, 1);
        if (count($parts) > 1) {
            $initials .= substr($parts[array_key_last($parts)], 0, 1);
        }

        return strtoupper($initials);
    }
}
