<?php

declare(strict_types=1);

namespace Atom\Validation;

use JsonSerializable;

final readonly class ValidationError implements JsonSerializable
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $field,
        public string $message,
        public string $code,
        public array $parameters = []
    ) {
    }

    public function forField(string $field): self
    {
        return new self($field, $this->message, $this->code, $this->parameters);
    }

    /**
     * @return array{field: string, message: string, code: string, parameters: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            "field" => $this->field,
            "message" => $this->message,
            "code" => $this->code,
            "parameters" => $this->parameters,
        ];
    }
}

