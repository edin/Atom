<?php

declare(strict_types=1);

namespace Atom\View\Render;

final readonly class HtmlString
{
    public function __construct(private string $html)
    {
    }

    public function toHtml(): string
    {
        return $this->html;
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
