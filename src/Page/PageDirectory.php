<?php

declare(strict_types=1);

namespace Atom\Page;

final readonly class PageDirectory
{
    public function __construct(
        public string $directory,
        public string $pathPrefix = ""
    ) {
    }
}
