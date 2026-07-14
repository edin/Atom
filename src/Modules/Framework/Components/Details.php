<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Details implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $summary = "";
    public bool $open = false;
    public string $class = "";

    public function render(): string
    {
        $summary = Html::tag("summary", ["class" => "atom-details__summary"], Html::escape($this->summary));
        $content = Html::tag("div", ["class" => "atom-details__content"], $this->content?->render() ?? "");

        return Html::tag("details", Html::mergeAttributes([
            "class" => Html::classes("atom-details", $this->class),
            "open" => $this->open,
        ], $this->attributes->all()), $summary . $content);
    }
}
