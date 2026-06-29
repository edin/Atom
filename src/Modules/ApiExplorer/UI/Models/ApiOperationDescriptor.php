<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer\UI\Models;

use Atom\Api\ApiEndpointDescriptor;

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
