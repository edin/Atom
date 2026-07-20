<?php

declare(strict_types=1);

namespace Showcase\Components;

use Atom\Page\Page;
use Atom\View\Component\Fragment;
use Atom\View\Component\TemplateComponent;
use Atom\View\Component\TemplateFragment;

final class ShowcaseLayout extends TemplateComponent
{
    public Page $page;

    public Fragment|TemplateFragment|null $content = null;

    public function title(): string
    {
        return property_exists($this->page, "title") ? (string) $this->page->title : "Atom Showcase";
    }

    public function contentWidth(): string
    {
        return property_exists($this->page, "contentWidth") ? (string) $this->page->contentWidth : "";
    }

}
