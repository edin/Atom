<?php

declare(strict_types=1);

namespace App\Pages;

use App\Models\Category;
use Atom\Page\PageRoute;

#[PageRoute("/articles/new", name: "articles.create")]
final class NewArticlePage extends AppPage
{
    public string $title = "New article - Atom Sample";

    /** @var list<Category> */
    public array $categories = [];

    public function get(): void
    {
        $this->categories = Category::query()
            ->orderBy("name")
            ->all();
    }
}
