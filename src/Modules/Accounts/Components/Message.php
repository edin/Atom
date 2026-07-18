<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class Message implements ComponentInterface
{
    public AttributeBag $attributes;
    public string $message = "";
    public string $class = "";

    public function render(): string
    {
        if ($this->message === "") {
            return "";
        }

        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("accounts-message", $this->class),
            "role" => "status",
        ], $this->attributes->all()), Html::escape($this->message));
    }
}
