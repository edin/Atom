<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

use Attribute;
use Atom\Validation\ValidationContext;
use Atom\Validation\ValidationError;
use DateTimeImmutable;
use DateTimeInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Date extends AbstractRule
{
    public function __construct(public ?string $format = null, ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(mixed $value, ValidationContext $context): ?ValidationError
    {
        if ($this->isEmpty($value) || $value instanceof DateTimeInterface) {
            return null;
        }

        if (!is_scalar($value)) {
            return $this->error($context, "date", "The field must be a valid date.");
        }

        if ($this->format !== null) {
            $date = DateTimeImmutable::createFromFormat($this->format, (string) $value);
            $errors = DateTimeImmutable::getLastErrors();
            $errorCount = $errors === false ? 0 : $errors["warning_count"] + $errors["error_count"];

            return $date !== false && $errorCount === 0
                ? null
                : $this->error($context, "date", "The field must match date format {format}.", ["format" => $this->format]);
        }

        try {
            new DateTimeImmutable((string) $value);
            return null;
        } catch (\Exception) {
            return $this->error($context, "date", "The field must be a valid date.");
        }
    }
}

