<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Showcase\Pages\AppPage;

#[PageRoute("/components/actions", name: "showcase.components.actions")]
final class ActionsPage extends AppPage
{
    public string $title = "Actions - Atom Showcase";

    #[State]
    public int $count = 0;

    #[PageAction("increment")]
    public function increment(): void
    {
        $this->count++;
    }
}
