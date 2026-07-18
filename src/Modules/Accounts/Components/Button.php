<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Button implements ComponentInterface
{
    public AttributeBag $attributes;
    public ?Fragment $content = null;
    public string $text = "";
    public string $type = "submit";
    public bool $disabled = false;
    public string $class = "";

    public function render(): string
    {
        return Html::tag("button", Html::mergeAttributes([
            "type" => $this->type,
            "class" => Html::classes("accounts-submit", $this->class),
            "disabled" => $this->disabled,
        ], $this->attributes->all()), $this->content?->renderOr(Html::escape($this->text)) ?? Html::escape($this->text));
    }
}
