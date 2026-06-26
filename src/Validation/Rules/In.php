<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class In extends AbstractRule
{
    /**
     * @param mixed[] $values
     */
    public function __construct(public array $values, ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        return in_array($value, $this->values, true)
            ? null
            : $this->error($context, "in", "The field value is not allowed.", ["values" => $this->values]);
    }
}

