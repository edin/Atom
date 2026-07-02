<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\TemplateFragment;
use Atom\View\Html;

final class Column implements ComponentInterface
{
    public ?TemplateFragment $content = null;
    public AttributeBag $attributes;
    public string $label = "";
    public string $field = "";
    public string $class = "";

    public function render(): string
    {
        return $this->renderHeader();
    }

    public function renderHeader(): string
    {
        return Html::tag("th", [
            "class" => Html::classes("atom-table__heading", $this->class),
        ], Html::escape($this->label));
    }

    public function renderCell(mixed $item, int $index, string $itemName): string
    {
        $value = $this->value($item);
        $content = $this->content !== null
            ? $this->content->render([
                $itemName => $item,
                "item" => $item,
                "row" => $item,
                "index" => $index,
                "value" => $value,
            ])
            : Html::escape($value);

        return Html::tag("td", Html::mergeAttributes([
            "class" => Html::classes("atom-table__cell", $this->class),
        ], $this->attributes->all()), $content);
    }

    private function value(mixed $item): mixed
    {
        if ($this->field === "") {
            return "";
        }

        $value = $item;
        foreach (explode(".", $this->field) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }

            if (is_object($value) && isset($value->{$segment})) {
                $value = $value->{$segment};
                continue;
            }

            return "";
        }

        return $value;
    }
}
