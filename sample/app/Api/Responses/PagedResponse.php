<?php

declare(strict_types=1);

namespace App\Api\Responses;

use Atom\ApiExplorer\Attributes\ArrayOf;

final class PagedResponse
{
    #[ArrayOf("T")]
    public array $items;

    public int $total;
    public int $page;
}
