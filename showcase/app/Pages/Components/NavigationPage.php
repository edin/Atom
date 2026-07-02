<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/navigation", name: "showcase.components.navigation")]
final class NavigationPage extends AppPage
{
    public string $title = "Navigation - Atom Showcase";
}
