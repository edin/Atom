<?php

declare(strict_types=1);

namespace Atom\Validation;

use Countable;
use JsonSerializable;

final class ValidationResult implements Countable, JsonSerializable
{
    /** @var ValidationError[] */
    private array $errors = [];

    public static function valid(): self
    {
        return new self();
    }

    public function add(ValidationError $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    public function merge(self $result): self
    {
        foreach ($result->all() as $error) {
            $this->add($error);
        }

        return $this;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function passed(): bool
    {
        return $this->isValid();
    }

    public function failed(): bool
    {
        return !$this->isValid();
    }

    public function hasErrorsFor(string $field): bool
    {
        return $this->errorsFor($field) !== [];
    }

    /**
     * @return ValidationError[]
     */
    public function errorsFor(string $field): array
    {
        return array_values(array_filter(
            $this->errors,
            static fn(ValidationError $error): bool => $error->field === $field
        ));
    }

    public function first(string $field): ?string
    {
        return $this->errorsFor($field)[0]->message ?? null;
    }

    /**
     * @return ValidationError[]
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, string[]>
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->errors as $error) {
            $messages[$error->field][] = $error->message;
        }

        return $messages;
    }

    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * @return array{valid: bool, errors: array<string, string[]>}
     */
    public function jsonSerialize(): array
    {
        return [
            "valid" => $this->isValid(),
            "errors" => $this->messages(),
        ];
    }
}

