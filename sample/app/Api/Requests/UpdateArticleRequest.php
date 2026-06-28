<?php

declare(strict_types=1);

namespace App\Api\Requests;

use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Validation\Rules\MaxLength;

#[Dto]
final class UpdateArticleRequest
{
    #[FromBody]
    #[MaxLength(120)]
    public ?string $title = null;

    #[FromBody]
    #[MaxLength(240)]
    public ?string $summary = null;

    #[FromBody("category_id")]
    public ?int $categoryId = null;

    #[FromBody]
    public ?string $body = null;

    #[FromBody]
    public ?bool $publish = null;
}
