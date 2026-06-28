<?php

declare(strict_types=1);

namespace Atom\ApiExplorer;

use JsonSerializable;

final readonly class ApiErrorResponseDescriptor implements JsonSerializable
{
    /**
     * @param ApiFieldDescriptor[] $fields
     */
    public function __construct(
        public int $status,
        public string $type,
        public ?string $description = null,
        public array $fields = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            "status" => $this->status,
            "type" => $this->type,
            "description" => $this->description,
            "fields" => $this->fields,
        ];
    }
}
