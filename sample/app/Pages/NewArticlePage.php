<?php

declare(strict_types=1);

namespace App\Pages;

use App\Models\Article;
use App\Models\Category;
use Atom\Http\Response;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Required;

#[PageRoute("/articles/new", name: "articles.create")]
final class NewArticlePage extends AppPage
{
    public string $title = "New article - Atom Sample";

    /** @var list<Category> */
    public array $categories = [];

    #[FromBody("category_id")]
    public int $categoryId = 1;

    #[FromBody]
    #[Required("Give this article a title.")]
    #[MaxLength(120)]
    public string $titleInput = "";

    #[FromBody]
    #[Required("Add a short summary.")]
    #[MaxLength(220)]
    public string $summary = "";

    #[FromBody]
    #[Required("Write the article body.")]
    public string $body = "";

    public function get(): void
    {
        $this->loadCategories();
    }

    #[PageAction("create")]
    public function create(Response $response): ?Response
    {
        if (!$this->validate()) {
            $this->loadCategories();
            return null;
        }

        $article = new Article();
        $article->categoryId = $this->categoryId;
        $article->title = $this->titleInput;
        $article->summary = $this->summary;
        $article->body = $this->body;
        $article->isPublished = true;
        $article->save();

        return $response->redirect("/articles");
    }

    private function loadCategories(): void
    {
        $this->categories = Category::query()->orderBy("name")->all();
    }
}
