<?php

declare(strict_types=1);

namespace App\Api\Requests;

use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromQuery;
use Atom\Validation\Rules\Min;

#[Dto]
final class ArticleListRequest
{
    #[FromQuery("q")]
    public ?string $search = null;

    #[FromQuery]
    #[Min(1)]
    public int $page = 1;

    #[FromQuery]
    public ?bool $published = null;
}
