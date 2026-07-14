<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Field implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $label = "";
    public string $name = "";
    public string $help = "";
    public ?string $for = null;
    public string $class = "";

    public function render(): string
    {
        $content = "";
        if ($this->label !== "") {
            $content .= Html::tag("span", ["class" => "atom-field-label"], Html::escape($this->label));
        }

        $content .= $this->content?->render() ?? "";

        if ($this->help !== "") {
            $content .= Html::tag("p", ["class" => "atom-field-help"], Html::escape($this->help));
        }

        return Html::tag("label", Html::mergeAttributes([
            "class" => Html::classes("atom-field", $this->class),
            "for" => $this->fieldId(),
        ], $this->attributes->all()), $content);
    }

    private function fieldId(): ?string
    {
        if ($this->for !== null) {
            return $this->for;
        }

        return $this->name === "" ? null : str_replace([".", "[", "]"], "-", $this->name);
    }
}
