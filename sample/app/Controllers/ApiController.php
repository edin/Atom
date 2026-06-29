<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Api\Requests\ArticleListRequest;
use App\Api\Requests\CreateArticleRequest;
use App\Api\Requests\UpdateArticleRequest;
use App\Api\Responses\ArticleResponse;
use App\Api\Responses\CategoryResponse;
use App\Api\Responses\DeleteArticleResponse;
use App\Api\Responses\NotFoundResponse;
use App\Api\Responses\PagedResponse;
use App\Api\Responses\ValidationErrorResponse;
use Atom\Api\Attributes\ErrorResponse;
use Atom\Api\Attributes\ResponseOf;
use Atom\Hydrator\Attributes\FromRoute;
use Atom\Router\Attributes\Controller;
use Atom\Router\Attributes\Delete;
use Atom\Router\Attributes\Get;
use Atom\Router\Attributes\Post;
use Atom\Router\Attributes\Put;

#[Controller("/api")]
final readonly class ApiController
{
    #[Get("articles")]
    #[ResponseOf(ArticleResponse::class)]
    public function articles(ArticleListRequest $query): PagedResponse
    {
        $response = new PagedResponse();
        $response->items = [$this->articleResponse(1, "Building Atom", true)];
        $response->total = 1;
        $response->page = $query->page;

        return $response;
    }

    #[Post("articles")]
    #[ErrorResponse(422, ValidationErrorResponse::class, "Request body failed validation.")]
    public function create(CreateArticleRequest $request): ArticleResponse
    {
        return $this->articleResponse(42, $request->title, $request->publish);
    }

    #[Get("articles/{id}")]
    #[ErrorResponse(404, NotFoundResponse::class, "Article was not found.")]
    public function article(#[FromRoute] int $id): ArticleResponse
    {
        return $this->articleResponse($id, "Article {$id}", true);
    }

    #[Put("articles/{id}")]
    #[ErrorResponse(404, NotFoundResponse::class, "Article was not found.")]
    #[ErrorResponse(422, ValidationErrorResponse::class, "Request body failed validation.")]
    public function update(#[FromRoute] int $id, UpdateArticleRequest $request): ArticleResponse
    {
        return $this->articleResponse($id, $request->title ?? "Article {$id}", $request->publish ?? true);
    }

    #[Delete("articles/{id}")]
    #[ErrorResponse(404, NotFoundResponse::class, "Article was not found.")]
    public function delete(#[FromRoute] int $id): DeleteArticleResponse
    {
        $response = new DeleteArticleResponse();
        $response->deleted = true;
        $response->id = $id;

        return $response;
    }

    private function articleResponse(int $id, string $title, bool $published): ArticleResponse
    {
        $category = new CategoryResponse();
        $category->id = 1;
        $category->name = "Framework";
        $category->slug = "framework";

        $article = new ArticleResponse();
        $article->id = $id;
        $article->title = $title;
        $article->summary = "Sample article returned by the API.";
        $article->published = $published;
        $article->category = $category;

        return $article;
    }
}
