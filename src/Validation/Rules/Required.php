<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Required extends AbstractRule
{
    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        return $this->isEmpty($value)
            ? $this->error($context, "required", "The field is required.")
            : null;
    }
}

