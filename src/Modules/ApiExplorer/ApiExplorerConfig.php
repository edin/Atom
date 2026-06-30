<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer;

final readonly class ApiExplorerConfig
{
    public function __construct(
        public string $resourcePath,
        public string $pagePath,
        public string $apiPathPrefix = "/api"
    ) {
    }
}
