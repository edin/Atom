<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Http\Request;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Showcase\Pages\AppPage;

#[PageRoute("/components/pagination", name: "showcase.components.pagination")]
final class PaginationPage extends AppPage
{
    public string $title = "Pagination - Atom Showcase";

    public int $page = 1;

    #[State]
    public int $localPage = 2;

    public int $total = 8;

    public function get(Request $request): void
    {
        $this->page = min(max(1, $request->query()->int("page", 1)), $this->total);
    }

    #[PageAction("setLocalPage")]
    public function setLocalPage(int $page): void
    {
        $this->localPage = min(max(1, $page), $this->total);
    }
}
