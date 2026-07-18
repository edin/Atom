<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class FormSection implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $title = "";
    public string $description = "";
    public string $class = "";

    public function render(): string
    {
        return Html::tag("section", Html::mergeAttributes([
            "class" => Html::classes("atom-form-section", $this->class),
        ], $this->attributes->all()), $this->header() . ($this->content?->render() ?? ""));
    }

    private function header(): string
    {
        if ($this->title === "" && $this->description === "") {
            return "";
        }

        $content = "";

        if ($this->title !== "") {
            $content .= Html::tag("h3", ["class" => "atom-form-section__title"], Html::escape($this->title));
        }

        if ($this->description !== "") {
            $content .= Html::tag("p", ["class" => "atom-form-section__description"], Html::escape($this->description));
        }

        return Html::tag("header", ["class" => "atom-form-section__header"], $content);
    }
}
