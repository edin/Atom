<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Pattern extends AbstractRule
{
    public function __construct(public string $pattern, ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        return is_scalar($value) && preg_match($this->pattern, (string) $value) === 1
            ? null
            : $this->error($context, "pattern", "The field format is invalid.", ["pattern" => $this->pattern]);
    }
}

