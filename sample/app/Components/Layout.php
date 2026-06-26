<?php

declare(strict_types=1);

namespace App\Components;

use Atom\Page\Page;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentView;
use Atom\View\Component\Fragment;

final class Layout implements ComponentInterface
{
    public Page $page;

    public ?Fragment $content = null;

    public function title(): string
    {
        return property_exists($this->page, "title") && is_string($this->page->title)
            ? $this->page->title
            : "Atom Sample";
    }

    public function render(): string
    {
        return ComponentView::render($this);
    }
}
