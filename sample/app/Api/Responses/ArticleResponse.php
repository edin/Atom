<?php

declare(strict_types=1);

namespace App\Api\Responses;

final class ArticleResponse
{
    public int $id;
    public string $title;
    public ?string $summary = null;
    public bool $published;
    public CategoryResponse $category;
}
