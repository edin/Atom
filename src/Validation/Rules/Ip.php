<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Ip extends AbstractRule
{
    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_IP) === false
            ? $this->error($context, "ip", "The field must be a valid IP address.")
            : null;
    }
}

