<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/layout", name: "showcase.components.layout")]
final class LayoutPage extends AppPage
{
    public string $title = "Layout - Atom Showcase";
}
