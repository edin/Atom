<?php

namespace Atom\Validation;

class ValidationResult
{
    private $errorMessage = "";
    private $isValid;

    private function __construct(string $errorMessage, bool $isValid)
    {
        $this->errorMessage = $errorMessage;
        $this->isValid = $isValid;
    }

    public static function success(): self
    {
        return new static("", true);
    }

    public static function failure(string $errorMessage): self
    {
        return new static($errorMessage, false);
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
