<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

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
    public string $class = "";

    public function render(): string
    {
        $tag = $this->href === null ? "button" : "a";
        $attributes = Html::mergeAttributes([
            "href" => $this->href,
            "type" => $tag === "button" ? $this->type : null,
            "class" => Html::classes("atom-button", $this->class),
            "data-variant" => $this->variant,
        ], $this->attributes->all());

        return Html::tag($tag, $attributes, $this->content());
    }

    private function content(): string
    {
        if ($this->content !== null) {
            return $this->content->renderOr(Html::escape($this->text));
        }

        return Html::escape($this->text);
    }

}
