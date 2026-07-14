<?php

declare(strict_types=1);

namespace Showcase\Components;

use Atom\Page\Page;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentView;
use Atom\View\Component\Fragment;

final class ShowcaseLayout implements ComponentInterface
{
    public Page $page;

    public ?Fragment $content = null;

    public function title(): string
    {
        return property_exists($this->page, "title") ? (string) $this->page->title : "Atom Showcase";
    }

    public function contentWidth(): string
    {
        return property_exists($this->page, "contentWidth") ? (string) $this->page->contentWidth : "";
    }

    public function render(): string
    {
        return ComponentView::render($this);
    }
}
