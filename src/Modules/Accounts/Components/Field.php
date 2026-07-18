<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class Field implements ComponentInterface
{
    public AttributeBag $attributes;
    public string $label;
    public string $name;
    public string $type = "text";
    public ?string $id = null;
    public ?string $value = null;
    public string $autocomplete = "";
    public bool $required = false;
    public bool $autofocus = false;
    public bool $disabled = false;
    public string $class = "";

    public function render(): string
    {
        $id = $this->id ?? str_replace([".", "[", "]"], "-", $this->name);
        $input = Html::voidTag("input", Html::mergeAttributes([
            "type" => $this->type,
            "id" => $id,
            "name" => $this->name,
            "value" => $this->value,
            "autocomplete" => $this->autocomplete,
            "required" => $this->required,
            "autofocus" => $this->autofocus,
            "disabled" => $this->disabled,
        ], $this->attributes->all()));

        return Html::tag("label", [
            "class" => Html::classes("accounts-field", $this->class),
            "for" => $id,
        ], Html::tag("span", [], Html::escape($this->label)) . $input);
    }
}
