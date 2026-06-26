<?php

declare(strict_types=1);

namespace App\Pages;

use App\Models\Article;
use Atom\Page\PageRoute;

#[PageRoute("/", name: "home")]
final class HomePage extends AppPage
{
    public int $articleCount = 0;

    /** @var list<Article> */
    public array $latest = [];

    public function get(): void
    {
        $this->articleCount = Article::query()
            ->where("is_published", true)
            ->total();
        $this->latest = Article::query()
            ->where("is_published", true)
            ->orderByDesc("created_at")
            ->limit(3)
            ->with("category")
            ->all();
    }
}
