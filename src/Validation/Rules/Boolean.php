<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Boolean extends AbstractRule
{
    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null
            ? $this->error($context, "boolean", "The field must be true or false.")
            : null;
    }
}

