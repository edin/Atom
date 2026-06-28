<?php

declare(strict_types=1);

namespace Atom\ApiExplorer;

use JsonSerializable;

final readonly class ApiEndpointDescriptor implements JsonSerializable
{
    /**
     * @param string[] $methods
     * @param ApiFieldDescriptor[] $requestFields
     * @param ApiFieldDescriptor[] $responseFields
     * @param ApiErrorResponseDescriptor[] $errorResponses
     */
    public function __construct(
        public array $methods,
        public string $path,
        public ?string $name,
        public ?string $title,
        public ?string $description,
        public string $handler,
        public ?string $controller,
        public ?string $action,
        public ?string $responseType,
        public array $requestFields,
        public array $responseFields = [],
        public array $errorResponses = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            "methods" => $this->methods,
            "path" => $this->path,
            "name" => $this->name,
            "title" => $this->title,
            "description" => $this->description,
            "handler" => $this->handler,
            "controller" => $this->controller,
            "action" => $this->action,
            "responseType" => $this->responseType,
            "requestFields" => $this->requestFields,
            "responseFields" => $this->responseFields,
            "errorResponses" => $this->errorResponses,
        ];
    }
}
