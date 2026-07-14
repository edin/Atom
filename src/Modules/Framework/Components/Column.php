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
    public ?TemplateFragment $actions = null;
    public AttributeBag $attributes;
    public string $label = "";
    public string $field = "";
    public string|bool $sort = false;
    public string $class = "";
    public string $align = "";

    public function render(): string
    {
        return $this->renderHeader();
    }

    public function renderHeader(string $activeSort = "", string $direction = "asc", string $sortAction = ""): string
    {
        $sortKey = $this->sortKey();
        $isSorted = $sortKey !== "" && $sortKey === $activeSort;
        $label = Html::escape($this->label);
        $content = $sortKey !== "" && $sortAction !== ""
            ? $this->sortButton($label, $sortKey, $isSorted, $direction, $sortAction)
            : $label;

        return Html::tag("th", [
            "class" => Html::classes("atom-table__heading", [
                "atom-table__heading--actions" => $this->isActionsColumn(),
                "is-sortable" => $sortKey !== "",
                "is-sorted" => $isSorted,
            ], $this->class),
            "data-align" => $this->align === "" && $this->isActionsColumn() ? "end" : $this->align,
            "aria-sort" => $isSorted ? ($this->normalizedDirection($direction) === "desc" ? "descending" : "ascending") : null,
        ], $content);
    }

    public function renderCell(mixed $item, int $index, string $itemName): string
    {
        $value = $this->value($item);
        $variables = [
            $itemName => $item,
            "item" => $item,
            "row" => $item,
            "index" => $index,
            "value" => $value,
        ];

        return Html::tag("td", Html::mergeAttributes([
            "class" => Html::classes("atom-table__cell", [
                "atom-table__cell--actions" => $this->isActionsColumn(),
            ], $this->class),
            "data-align" => $this->align === "" && $this->isActionsColumn() ? "end" : $this->align,
        ], $this->attributes->all()), $this->cellContent($value, $variables));
    }

    private function isActionsColumn(): bool
    {
        return $this->actions !== null;
    }

    private function sortKey(): string
    {
        if ($this->sort === true) {
            return $this->field;
        }

        if (is_string($this->sort)) {
            return $this->sort;
        }

        return "";
    }

    private function sortButton(string $label, string $sortKey, bool $isSorted, string $direction, string $sortAction): string
    {
        $nextDirection = $isSorted && $this->normalizedDirection($direction) === "asc" ? "desc" : "asc";
        $action = str_replace(["{sort}", "{direction}"], [$sortKey, $nextDirection], $sortAction);
        $indicator = $isSorted
            ? Html::tag("span", [
                "class" => "atom-table__sort-indicator",
                "data-direction" => $this->normalizedDirection($direction),
                "aria-hidden" => "true",
            ])
            : "";

        return Html::tag("button", [
            "type" => "button",
            "class" => "atom-table__sort",
            "atom:action" => $action,
        ], Html::tag("span", [], $label) . $indicator);
    }

    private function normalizedDirection(string $direction): string
    {
        return strtolower($direction) === "desc" ? "desc" : "asc";
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function cellContent(mixed $value, array $variables): string
    {
        if ($this->actions !== null) {
            return Html::tag("div", ["class" => "atom-table__actions"], $this->actions->render($variables));
        }

        if ($this->content !== null) {
            return $this->content->render($variables);
        }

        return Html::escape($value);
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
