<?php

declare(strict_types=1);

namespace App\Api\Responses;

use Atom\Api\Attributes\ArrayOf;

final class PagedResponse
{
    #[ArrayOf]
    public array $items;

    public int $total;
    public int $page;
}
