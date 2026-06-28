<?php

declare(strict_types=1);

namespace Atom\Http;

final readonly class StaticFileRouteMetadata
{
    public function __construct(
        public string $directory
    ) {
    }
}
