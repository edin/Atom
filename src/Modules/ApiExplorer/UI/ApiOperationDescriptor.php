<?php

declare(strict_types=1);

namespace Atom\ApiExplorer\UI;

use Atom\ApiExplorer\ApiEndpointDescriptor;

final readonly class ApiOperationDescriptor
{
    public function __construct(
        public string $method,
        public ApiEndpointDescriptor $endpoint
    ) {
    }

    public function path(): string
    {
        return $this->endpoint->path;
    }
}
