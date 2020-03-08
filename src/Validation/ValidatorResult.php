<?php

namespace Atom\Validation;

class ValidatorResult
{
    private const Success = 1;
    private const Failure = 2;

    private $errorMessage = "";
    private $attributes = [];
    private $status;
    private $value;

    private function __construct(int $status, $value, string $errorMessage, array $attributes)
    {
        $this->status = $status;
        $this->value = $value;
        $this->errorMessage = $errorMessage;
        $this->attributes = $attributes;
    }

    public static function success($value): self
    {
        return new static(self::Success, $value, "", []);
    }

    public static function failure(string $errorMessage, array $attributes = []): self
    {
        return new static(self::Failure, null, $errorMessage, $attributes);
    }

    public function getValue()
    {
        return $this->value;
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
        return $this->status == self::Success;
    }

    public function isFailure(): bool
    {
        return $this->status == self::Failure;
    }
}
