<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Breadcrumb implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $text = "";
    public string $href = "";
    public string $class = "";

    public function render(): string
    {
        $tag = $this->href === "" ? "span" : "a";

        return Html::tag($tag, Html::mergeAttributes([
            "href" => $this->href,
            "class" => Html::classes("atom-breadcrumb", $this->class),
        ], $this->attributes->all()), $this->content());
    }

    private function content(): string
    {
        if ($this->content !== null) {
            return $this->content->renderOr(Html::escape($this->text));
        }

        return Html::escape($this->text);
    }
}
