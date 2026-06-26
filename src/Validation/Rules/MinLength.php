<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class MinLength extends AbstractRule
{
    public function __construct(public int $value, ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        return is_string($value) && $this->length($value) >= $this->value
            ? null
            : $this->error($context, "min_length", "The field must be at least {min} characters.", ["min" => $this->value]);
    }

    private function length(string $value): int
    {
        return function_exists("mb_strlen") ? mb_strlen($value) : strlen($value);
    }
}

