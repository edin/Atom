<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\Children;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\TemplateFragment;
use Atom\View\Html;

final class Table implements ComponentInterface
{
    /** @var Column[] */
    #[Children("Column", Column::class)]
    public array $columns = [];
    public ?TemplateFragment $content = null;
    public ?TemplateFragment $toolbar = null;
    public ?TemplateFragment $empty = null;
    public ?TemplateFragment $summary = null;
    public ?TemplateFragment $pagination = null;
    public AttributeBag $attributes;
    public iterable $items = [];
    public string $as = "row";
    public string $emptyText = "No records found.";
    public string $sort = "";
    public string $direction = "asc";
    public string $sortAction = "";
    public string $class = "";

    public function render(): string
    {
        $table = Html::tag("div", ["class" => "atom-table-wrap"], Html::tag(
            "table",
            Html::mergeAttributes([
                "class" => Html::classes("atom-table", $this->class),
            ], $this->attributes->all()),
            $this->head() . $this->body()
        ));

        if ($this->toolbar === null && $this->summary === null && $this->pagination === null) {
            return $table;
        }

        return Html::tag("div", ["class" => "atom-table-stack"], $this->toolbar() . $table . $this->footer());
    }

    private function head(): string
    {
        if ($this->columns === []) {
            return "";
        }

        $cells = "";
        foreach ($this->columns as $column) {
            $cells .= $column->renderHeader($this->sort, $this->direction, $this->sortAction);
        }

        return Html::tag("thead", [], Html::tag("tr", [], $cells));
    }

    private function body(): string
    {
        $rows = "";
        $index = 0;
        foreach ($this->items as $item) {
            $cells = "";
            foreach ($this->columns as $column) {
                $cells .= $column->renderCell($item, $index, $this->as);
            }

            $rows .= Html::tag("tr", [], $cells);
            $index++;
        }

        if ($rows === "") {
            $columns = max(1, count($this->columns));
            $rows = Html::tag("tr", [], Html::tag(
                "td",
                ["class" => "atom-table__empty", "colspan" => $columns],
                $this->empty?->render($this->sourceVariables()) ?? Html::escape($this->emptyText)
            ));
        }

        return Html::tag("tbody", [], $rows);
    }

    private function summary(): string
    {
        if ($this->summary === null) {
            return "";
        }

        return Html::tag("div", ["class" => "atom-table__summary"], $this->summary->render($this->sourceVariables()));
    }

    private function toolbar(): string
    {
        if ($this->toolbar === null) {
            return "";
        }

        return Html::tag("div", ["class" => "atom-table__toolbar"], $this->toolbar->render($this->sourceVariables()));
    }

    private function pagination(): string
    {
        return $this->pagination?->render($this->sourceVariables()) ?? "";
    }

    private function footer(): string
    {
        if ($this->totalItems() === 0) {
            return "";
        }

        $summary = $this->summary();
        $pagination = $this->pagination();

        if ($summary === "" && $pagination === "") {
            return "";
        }

        return Html::tag("div", ["class" => "atom-table__footer"], $summary . $pagination);
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceVariables(): array
    {
        $page = $this->sourceValue(["page", "getCurrentPage"], 1);
        $pageSize = $this->sourceValue(["pageSize", "getPageSize"], $this->itemCount());
        $total = $this->sourceValue(["totalCount", "getTotalCount"], $this->itemCount());
        $from = $total === 0 ? 0 : (($page - 1) * max(1, $pageSize)) + 1;
        $to = $total === 0 ? 0 : min($total, $from + max(1, $pageSize) - 1);

        return [
            "source" => $this->items,
            "items" => $this->items,
            "table" => $this,
            "currentPage" => $page,
            "pageSize" => $pageSize,
            "total" => $total,
            "from" => $from,
            "to" => $to,
        ];
    }

    /**
     * @param string[] $methods
     */
    private function sourceValue(array $methods, int $fallback): int
    {
        if (!is_object($this->items)) {
            return $fallback;
        }

        foreach ($methods as $method) {
            if (method_exists($this->items, $method)) {
                return (int) $this->items->{$method}();
            }
        }

        return $fallback;
    }

    private function itemCount(): int
    {
        if (is_countable($this->items)) {
            return count($this->items);
        }

        return 0;
    }

    private function totalItems(): int
    {
        return $this->sourceValue(["totalCount", "getTotalCount"], $this->itemCount());
    }
}
