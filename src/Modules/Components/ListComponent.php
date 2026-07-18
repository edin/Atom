<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class ListComponent implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public iterable $items = [];
    public string $as = "item";
    public string $class = "";
    public string $divided = "";

    public function render(): string
    {
        return Html::tag("ul", Html::mergeAttributes([
            "class" => Html::classes("atom-list", $this->class),
            "data-divided" => $this->divided,
        ], $this->attributes->all()), $this->itemsHtml());
    }

    private function itemsHtml(): string
    {
        if ($this->content === null) {
            return "";
        }

        $html = "";
        $index = 0;

        foreach ($this->items as $item) {
            $html .= $this->content->render([
                $this->as => $item,
                "item" => $item,
                "row" => $item,
                "index" => $index,
            ]);
            $index++;
        }

        return $index === 0 ? $this->content->render() : $html;
    }
}
