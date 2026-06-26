<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Max extends AbstractRule
{
    public function __construct(public float $value, ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value) || !is_numeric($value)) {
            return null;
        }

        return (float) $value > $this->value
            ? $this->error($context, "max", "The field must be at most {max}.", ["max" => $this->value])
            : null;
    }
}

