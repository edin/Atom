<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer;

final readonly class ApiExplorerRouteMetadata
{
    public function __construct(
        public string $resourcePath,
        public string $apiPathPrefix = "/api"
    ) {
    }
}
