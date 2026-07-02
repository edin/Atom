<?php

declare(strict_types=1);

namespace Showcase\Pages;

use Atom\Page\PageRoute;

#[PageRoute("/", name: "showcase.home")]
final class HomePage extends AppPage
{
    public string $title = "Atom Showcase";
}
