<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Email extends AbstractRule
{
    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) === false
            ? $this->error($context, "email", "The field must be a valid email address.")
            : null;
    }
}

