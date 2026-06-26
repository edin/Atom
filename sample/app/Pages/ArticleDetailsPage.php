<?php

declare(strict_types=1);

namespace App\Pages;

use App\Models\Article;
use Atom\Http\Response;
use Atom\Page\PageRoute;

#[PageRoute("/articles/{id}", name: "articles.show")]
final class ArticleDetailsPage extends AppPage
{
    public ?Article $article = null;

    public function get(string $id, Response $response): ?Response
    {
        $article = Article::query()
            ->where("id", (int) $id)
            ->with("category")
            ->first();

        $this->article = $article instanceof Article ? $article : null;
        if ($this->article === null) {
            return $response->status(404)->content("Article not found");
        }

        $this->title = $this->article->title . " - Atom Sample";

        return null;
    }
}
