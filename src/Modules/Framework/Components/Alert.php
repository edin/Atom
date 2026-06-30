<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Alert implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $text = "";
    public string $variant = "";
    public string $role = "status";
    public string $class = "";

    public function render(): string
    {
        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-alert", $this->class),
            "data-variant" => $this->variant,
            "role" => $this->role,
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
