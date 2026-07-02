<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/buttons", name: "showcase.components.buttons")]
final class ButtonsPage extends AppPage
{
    public string $title = "Buttons - Atom Showcase";

    #[PageAction("noop")]
    public function noop(): void
    {
    }
}
