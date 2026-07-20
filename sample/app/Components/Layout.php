<?php

declare(strict_types=1);

namespace App\Components;

use Atom\Page\Page;
use Atom\View\Component\Fragment;
use Atom\View\Component\TemplateComponent;
use Atom\View\Component\TemplateFragment;

final class Layout extends TemplateComponent
{
    public Page $page;

    public Fragment|TemplateFragment|null $content = null;

    public function title(): string
    {
        return property_exists($this->page, "title") && is_string($this->page->title)
            ? $this->page->title
            : "Atom Sample";
    }

}
