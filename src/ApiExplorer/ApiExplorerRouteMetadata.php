<?php

declare(strict_types=1);

namespace Atom\ApiExplorer;

final readonly class ApiExplorerRouteMetadata
{
    public function __construct(
        public string $resourcePath,
        public string $apiPathPrefix = "/api"
    ) {
    }
}
