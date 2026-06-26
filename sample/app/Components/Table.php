<?php

declare(strict_types=1);

namespace App\Components;

use Atom\View\Component\Children;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;

final class Table implements ComponentInterface
{
    /** @var Column[] */
    #[Children("Column", Column::class)]
    public array $columns = [];

    /** @var iterable<object|array<string, mixed>> */
    public iterable $items = [];

    public ?Fragment $content = null;

    public function render(): string
    {
        $html = '<div class="data-table"><table><thead><tr>';

        foreach ($this->columns as $column) {
            $html .= "<th>" . $column->header() . "</th>";
        }

        $html .= "</tr></thead><tbody>";

        foreach ($this->items as $item) {
            $html .= "<tr>";

            foreach ($this->columns as $column) {
                $html .= '<td data-label="' . $this->escape($column->plainTitle()) . '">'
                    . $column->cell()->render(["item" => $item])
                    . "</td>";
            }

            $html .= "</tr>";
        }

        return $html . "</tbody></table></div>";
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }
}
