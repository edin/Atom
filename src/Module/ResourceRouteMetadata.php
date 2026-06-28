<?php

declare(strict_types=1);

namespace Atom\Module;

final readonly class ResourceRouteMetadata
{
    public function __construct(
        public string $file,
        public string $contentType
    ) {
    }
}
