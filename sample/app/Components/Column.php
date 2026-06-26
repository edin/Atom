<?php

declare(strict_types=1);

namespace App\Components;

use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;

final class Column implements ComponentInterface
{
    public string $title = "";

    public ?Fragment $header = null;

    public ?Fragment $cell = null;

    public ?Fragment $content = null;

    public function render(): string
    {
        return "";
    }

    public function header(): string
    {
        return $this->header?->renderOr($this->escape($this->title)) ?? $this->escape($this->title);
    }

    public function cell(): Fragment
    {
        return $this->cell ?? $this->content ?? new Fragment(static fn(): string => "");
    }

    public function plainTitle(): string
    {
        return trim(strip_tags($this->header?->render() ?? $this->title));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }
}
