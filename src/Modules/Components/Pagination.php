<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\FromContext;
use Atom\View\Html;

final class Pagination implements ComponentInterface
{
    public AttributeBag $attributes;
    #[FromContext("source")]
    public mixed $source = null;
    public int $page = 1;
    public int $total = 1;
    public int $window = 5;
    public string $href = "?page={page}";
    public string $action = "";
    public bool $navigate = false;
    public bool $preserveState = false;
    public string $label = "Pagination";
    public string $class = "";

    public function render(): string
    {
        $page = $this->sourcePage();
        $total = $this->sourceTotalPages();
        $items = $this->pageItem("Previous", $page - 1, $page === 1);

        foreach ($this->pageRange($page, $total) as $number) {
            $items .= $this->pageItem((string) $number, $number, false, $number === $page);
        }

        $items .= $this->pageItem("Next", $page + 1, $page === $total);

        return Html::tag("nav", Html::mergeAttributes([
            "class" => Html::classes("atom-pagination", $this->class),
            "aria-label" => $this->label,
        ], $this->attributes->all()), $items);
    }

    private function sourcePage(): int
    {
        $page = $this->sourceValue(["page", "getCurrentPage"], $this->page);

        return min(max(1, $page), $this->sourceTotalPages());
    }

    private function sourceTotalPages(): int
    {
        return max(1, $this->sourceValue(["totalPages", "getTotalPages"], $this->total));
    }

    /**
     * @param string[] $methods
     */
    private function sourceValue(array $methods, int $fallback): int
    {
        if (!is_object($this->source)) {
            return $fallback;
        }

        foreach ($methods as $method) {
            if (method_exists($this->source, $method)) {
                return (int) $this->source->{$method}();
            }
        }

        return $fallback;
    }

    /**
     * @return int[]
     */
    private function pageRange(int $page, int $total): array
    {
        $window = max(1, $this->window);
        $half = intdiv($window, 2);
        $start = max(1, $page - $half);
        $end = min($total, $start + $window - 1);
        $start = max(1, $end - $window + 1);

        return range($start, $end);
    }

    private function pageItem(string $label, int $page, bool $disabled = false, bool $active = false): string
    {
        $attributes = [
            "class" => Html::classes("atom-pagination__item", ["is-active" => $active, "is-disabled" => $disabled]),
            "aria-current" => $active ? "page" : null,
            "aria-disabled" => $disabled ? "true" : null,
        ];

        $tag = "span";
        if (!$disabled && $this->action !== "") {
            $tag = "button";
            $attributes["type"] = "button";
            $attributes["atom:action"] = str_replace("{page}", (string) $page, $this->action);
        } elseif (!$disabled) {
            $tag = "a";
            $attributes["href"] = str_replace("{page}", (string) $page, $this->href);
            $attributes["atom:navigate"] = $this->navigate;
            $attributes["atom:preserve-state"] = $this->preserveState;
        }

        return Html::tag($tag, $attributes, Html::escape($label));
    }
}
