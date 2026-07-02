<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/composition", name: "showcase.components.composition")]
final class CompositionPage extends AppPage
{
    public string $title = "Composition - Atom Showcase";
}
