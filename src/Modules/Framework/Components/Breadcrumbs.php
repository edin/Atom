<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\Children;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Breadcrumbs implements ComponentInterface
{
    /** @var Breadcrumb[] */
    #[Children("Breadcrumb", Breadcrumb::class)]
    public array $items = [];
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $label = "Breadcrumb";
    public string $class = "";

    public function render(): string
    {
        $items = "";
        foreach ($this->items as $item) {
            $items .= Html::tag("li", ["class" => "atom-breadcrumbs__item"], $item->render());
        }

        $items .= $this->content?->render() ?? "";

        return Html::tag("nav", Html::mergeAttributes([
            "class" => Html::classes("atom-breadcrumbs", $this->class),
            "aria-label" => $this->label,
        ], $this->attributes->all()), Html::tag("ol", ["class" => "atom-breadcrumbs__list"], $items));
    }
}
