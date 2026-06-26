<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Length extends AbstractRule
{
    public function __construct(public int $min, public int $max, ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        $length = is_string($value)
            ? (function_exists("mb_strlen") ? mb_strlen($value) : strlen($value))
            : null;

        return $length !== null && $length >= $this->min && $length <= $this->max
            ? null
            : $this->error($context, "length", "The field must be between {min} and {max} characters.", [
                "min" => $this->min,
                "max" => $this->max,
            ]);
    }
}

