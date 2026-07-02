<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/icons", name: "showcase.components.icons")]
final class IconsPage extends AppPage
{
    public string $title = "Icons - Atom Showcase";
}
