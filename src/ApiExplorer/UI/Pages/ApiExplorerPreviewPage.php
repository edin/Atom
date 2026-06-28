<?php

declare(strict_types=1);

namespace Atom\ApiExplorer\UI\Pages;

use Atom\ApiExplorer\ApiDescription;
use Atom\ApiExplorer\ApiEndpointDescriptor;
use Atom\ApiExplorer\ApiErrorResponseDescriptor;
use Atom\ApiExplorer\ApiFieldDescriptor;
use Atom\ApiExplorer\UI\ApiOperationDescriptor;
use Atom\Http\Request;
use Atom\Page\Page;
use Atom\Page\PageRoute;

#[PageRoute("/preview")]
final class ApiExplorerPreviewPage extends Page
{
    public string $title = "API Explorer";
    public ApiDescription $description;
    /** @var ApiOperationDescriptor[] */
    public array $operations = [];
    public int $selectedId = 0;

    public function get(?Request $request = null): void
    {
        $this->description = new ApiDescription([
            new ApiEndpointDescriptor(
                ["GET"],
                "/api/articles",
                "articles.index",
                "List articles",
                "Returns published articles with optional search and paging.",
                "App\\Controllers\\ApiController::articles",
                "App\\Controllers\\ApiController",
                "articles",
                "ArticleListResponse",
                [
                    new ApiFieldDescriptor("search", "query", "q", "?string", false, null),
                    new ApiFieldDescriptor("page", "query", "page", "int", false, null, ["Min"]),
                    new ApiFieldDescriptor("published", "query", "published", "?bool", false, null),
                ],
                [
                    new ApiFieldDescriptor("items", "response", "items", "ArticleResponse[]", true, "ArticleListResponse", [], [
                        new ApiFieldDescriptor("id", "response", "id", "int", true, "ArticleResponse"),
                        new ApiFieldDescriptor("title", "response", "title", "string", true, "ArticleResponse"),
                        new ApiFieldDescriptor("author", "response", "author", "AuthorResponse", true, "ArticleResponse", [], [
                            new ApiFieldDescriptor("id", "response", "id", "int", true, "AuthorResponse"),
                            new ApiFieldDescriptor("name", "response", "name", "string", true, "AuthorResponse"),
                        ]),
                    ]),
                    new ApiFieldDescriptor("total", "response", "total", "int", true, "ArticleListResponse"),
                    new ApiFieldDescriptor("page", "response", "page", "int", true, "ArticleListResponse"),
                ]
            ),
            new ApiEndpointDescriptor(
                ["POST"],
                "/api/articles",
                "articles.create",
                "Create article",
                "Creates a draft article and returns the stored model.",
                "App\\Controllers\\ApiController::create",
                "App\\Controllers\\ApiController",
                "create",
                "ArticleResponse",
                [
                    new ApiFieldDescriptor("title", "body", "title", "string", true, "CreateArticleDto", ["Required", "MaxLength"]),
                    new ApiFieldDescriptor("summary", "body", "summary", "?string", false, "CreateArticleDto", ["MaxLength"]),
                    new ApiFieldDescriptor("categoryId", "body", "category_id", "int", true, "CreateArticleDto", ["Required"]),
                    new ApiFieldDescriptor("publish", "body", "publish", "bool", false, "CreateArticleDto"),
                ],
                [
                    new ApiFieldDescriptor("id", "response", "id", "int", true, "ArticleResponse"),
                    new ApiFieldDescriptor("title", "response", "title", "string", true, "ArticleResponse"),
                    new ApiFieldDescriptor("summary", "response", "summary", "?string", false, "ArticleResponse"),
                    new ApiFieldDescriptor("published", "response", "published", "bool", true, "ArticleResponse"),
                ],
                [
                    new ApiErrorResponseDescriptor(422, "ValidationErrorResponse", "Request body failed validation.", [
                        new ApiFieldDescriptor("message", "response", "message", "string", true, "ValidationErrorResponse"),
                        new ApiFieldDescriptor("errors", "response", "errors", "ValidationErrorItem[]", true, "ValidationErrorResponse", [], [
                            new ApiFieldDescriptor("field", "response", "field", "string", true, "ValidationErrorItem"),
                            new ApiFieldDescriptor("message", "response", "message", "string", true, "ValidationErrorItem"),
                        ]),
                    ]),
                ]
            ),
            new ApiEndpointDescriptor(
                ["GET", "PUT", "DELETE"],
                "/api/articles/{id}",
                "articles.item",
                "Article item",
                "Reads, updates, or deletes a single article.",
                "App\\Controllers\\ApiController::article",
                "App\\Controllers\\ApiController",
                "article",
                "ArticleResponse",
                [
                    new ApiFieldDescriptor("id", "route", "id", "int", true, null),
                    new ApiFieldDescriptor("ifMatch", "header", "If-Match", "?string", false, null),
                ],
                [
                    new ApiFieldDescriptor("id", "response", "id", "int", true, "ArticleResponse"),
                    new ApiFieldDescriptor("title", "response", "title", "string", true, "ArticleResponse"),
                    new ApiFieldDescriptor("summary", "response", "summary", "?string", false, "ArticleResponse"),
                    new ApiFieldDescriptor("published", "response", "published", "bool", true, "ArticleResponse"),
                ],
                [
                    new ApiErrorResponseDescriptor(404, "NotFoundResponse", "Article was not found.", [
                        new ApiFieldDescriptor("message", "response", "message", "string", true, "NotFoundResponse"),
                    ]),
                ]
            ),
        ]);

        $this->operations = $this->operations($this->description);
        $this->selectedId = $this->normalizeSelectedId($request?->query()->int("id", 0) ?? 0);
    }

    public function selectedOperation(): ?ApiOperationDescriptor
    {
        return $this->operations[$this->selectedId] ?? null;
    }

    private function normalizeSelectedId(int $id): int
    {
        if ($this->operations === []) {
            return 0;
        }

        return max(0, min($id, count($this->operations) - 1));
    }

    /**
     * @return ApiOperationDescriptor[]
     */
    private function operations(ApiDescription $description): array
    {
        $operations = [];

        foreach ($description->endpoints as $endpoint) {
            foreach ($endpoint->methods as $method) {
                $operations[] = new ApiOperationDescriptor($method, $endpoint);
            }
        }

        return $operations;
    }
}
