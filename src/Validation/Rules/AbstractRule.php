<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;
use Atom\Validation\ValidationRuleInterface;

abstract readonly class AbstractRule implements ValidationRuleInterface
{
    public function __construct(protected ?string $message = null)
    {
    }

    protected function isEmpty(mixed $value): bool
    {
        return $value === null ||
            (is_string($value) && trim($value) === "") ||
            (is_array($value) && $value === []);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function error(ValidationContext $context, string $code, string $message, array $parameters = []): ValidationError
    {
        return new ValidationError(
            $context->field,
            $this->message ?? $message,
            $code,
            $parameters
        );
    }
}

