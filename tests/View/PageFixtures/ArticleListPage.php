<?php

declare(strict_types=1);

namespace Atom\Tests\View\PageFixtures;

use Atom\Page\Page;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;

#[PageRoute("/articles", name: "articles.index", method: ["GET", "POST"])]
final class ArticleListPage extends Page
{
    public string $title = "Articles";

    #[PageAction("refresh", method: "post")]
    public function refresh(): void
    {
    }
}
