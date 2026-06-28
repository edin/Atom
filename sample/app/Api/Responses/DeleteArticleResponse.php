<?php

declare(strict_types=1);

namespace App\Api\Responses;

final class DeleteArticleResponse
{
    public bool $deleted;
    public int $id;
}
