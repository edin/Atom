<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Article;
use Atom\Router\Attributes\Controller;
use Atom\Router\Attributes\Get;

#[Controller("/api")]
final readonly class ApiController
{
    #[Get("articles")]
    public function articles(): array
    {
        return array_map(
            static fn(Article $article): array => [
                "id" => $article->id,
                "title" => $article->title,
                "category" => $article->category?->name,
                "published" => $article->isPublished,
            ],
            Article::query()
                ->where("is_published", true)
                ->orderByDesc("created_at")
                ->with("category")
                ->all()
        );
    }
}
