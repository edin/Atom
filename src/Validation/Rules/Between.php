<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Between extends AbstractRule
{
    public function __construct(public float $min, public float $max, ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value) || !is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return $number < $this->min || $number > $this->max
            ? $this->error($context, "between", "The field must be between {min} and {max}.", [
                "min" => $this->min,
                "max" => $this->max,
            ])
            : null;
    }
}

