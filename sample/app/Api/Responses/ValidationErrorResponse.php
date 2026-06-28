<?php

declare(strict_types=1);

namespace App\Api\Responses;

use Atom\ApiExplorer\Attributes\ArrayOf;

final class ValidationErrorResponse
{
    public string $message;

    #[ArrayOf(ValidationErrorItemResponse::class)]
    public array $errors;
}
