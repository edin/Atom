<?php

declare(strict_types=1);

namespace App\Api\Responses;

final class ValidationErrorItemResponse
{
    public string $field;
    public string $message;
}
