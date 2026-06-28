<?php

declare(strict_types=1);

namespace Atom\ApiExplorer;

use JsonSerializable;

final readonly class ApiFieldDescriptor implements JsonSerializable
{
    /**
     * @param string[] $validationRules
     * @param ApiFieldDescriptor[] $children
     */
    public function __construct(
        public string $name,
        public string $source,
        public string $sourceName,
        public ?string $type,
        public bool $required,
        public ?string $model,
        public array $validationRules = [],
        public array $children = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "source" => $this->source,
            "sourceName" => $this->sourceName,
            "type" => $this->type,
            "required" => $this->required,
            "model" => $this->model,
            "validationRules" => $this->validationRules,
            "children" => $this->children,
        ];
    }
}
