<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class Skeleton implements ComponentInterface
{
    public AttributeBag $attributes;
    public string $width = "100%";
    public string $height = "0.85rem";
    public string $shape = "text";
    public string $class = "";

    public function render(): string
    {
        $attributes = $this->attributes->all();
        $customStyle = $attributes["style"] ?? null;
        unset($attributes["style"]);

        $style = "--atom-skeleton-width: {$this->width}; --atom-skeleton-height: {$this->height};";
        if (is_string($customStyle) && trim($customStyle) !== "") {
            $style .= " " . $customStyle;
        }

        return Html::tag("span", Html::mergeAttributes([
            "class" => Html::classes("atom-skeleton", $this->class),
            "data-shape" => $this->shape,
            "style" => $style,
            "aria-hidden" => "true",
        ], $attributes), "");
    }
}
