<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\Children;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Table implements ComponentInterface
{
    /** @var Column[] */
    #[Children("Column", Column::class)]
    public array $columns = [];
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public iterable $items = [];
    public string $as = "row";
    public string $empty = "No records found.";
    public string $class = "";

    public function render(): string
    {
        return Html::tag("div", ["class" => "atom-table-wrap"], Html::tag(
            "table",
            Html::mergeAttributes([
                "class" => Html::classes("atom-table", $this->class),
            ], $this->attributes->all()),
            $this->head() . $this->body()
        ));
    }

    private function head(): string
    {
        if ($this->columns === []) {
            return "";
        }

        $cells = "";
        foreach ($this->columns as $column) {
            $cells .= $column->renderHeader();
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
                Html::escape($this->empty)
            ));
        }

        return Html::tag("tbody", [], $rows);
    }
}
