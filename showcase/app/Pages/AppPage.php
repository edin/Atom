<?php

declare(strict_types=1);

namespace Showcase\Pages;

use Atom\Page\Page;
use Showcase\Components\ShowcaseLayout;

abstract class AppPage extends Page
{
    public ?string $layout = ShowcaseLayout::class;

    public string $title = "Atom Showcase";
}
