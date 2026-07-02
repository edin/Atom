<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/badges", name: "showcase.components.badges")]
final class BadgesPage extends AppPage
{
    public string $title = "Badges - Atom Showcase";
}
