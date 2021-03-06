<?php

declare(strict_types=1);

namespace Atom\Validation;

class ErrorMessage
{
    public array $attributes;
    public string $message;

    public function __construct(string $message, array $attributes)
    {
        $this->message = $message;
        $this->attributes = $attributes;
    }
}
