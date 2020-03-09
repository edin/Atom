<?php

namespace Atom\Validation;

final class ValidationResult
{
    private $errorMessage = "";
    private $attributes = [];
    private $isValid;

    private function __construct(string $errorMessage, array $attributes, bool $isValid)
    {
        $this->errorMessage = $errorMessage;
        $this->attributes = $attributes;
        $this->isValid = $isValid;
    }

    public static function success(): self
    {
        return new static("", [], true);
    }

    public static function failure(string $errorMessage, array $attributes = []): self
    {
        return new static($errorMessage, $attributes, false);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function isSuccess(): bool
    {
        return $this->isValid;
    }

    public function isFailure(): bool
    {
        return !$this->isValid;
    }
}
