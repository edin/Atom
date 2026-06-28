<?php

declare(strict_types=1);

namespace App\Api\Requests;

use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Required;

#[Dto]
final class CreateArticleRequest
{
    #[FromBody]
    #[Required]
    #[MaxLength(120)]
    public string $title;

    #[FromBody]
    #[MaxLength(240)]
    public ?string $summary = null;

    #[FromBody("category_id")]
    #[Required]
    public int $categoryId;

    #[FromBody]
    public string $body = "";

    #[FromBody]
    public bool $publish = false;
}
