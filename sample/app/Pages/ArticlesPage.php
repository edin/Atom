<?php

declare(strict_types=1);

namespace App\Pages;

use App\Models\Article;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;

#[PageRoute("/articles", name: "articles.index")]
final class ArticlesPage extends AppPage
{
    public string $title = "Articles - Atom Sample";

    /** @var list<Article> */
    public array $articles = [];

    public function get(): void
    {
        $this->articles = Article::query()
            ->where("is_published", true)
            ->orderByDesc("created_at")
            ->with("category")
            ->all();
    }

    #[PageAction]
    public function create(Request $request, Response $response): Response
    {
        $article = new Article();
        $article->categoryId = $request->post()->int("category_id", 1);
        $article->title = trim($request->post()->string("title"));
        $article->summary = trim($request->post()->string("summary"));
        $article->body = trim($request->post()->string("body"));
        $article->isPublished = true;

        if ($article->title !== "" && $article->body !== "") {
            $article->save();
        }

        return $response->redirect("/articles");
    }
}
