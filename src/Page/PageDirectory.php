<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Http\MiddlewareInterface;

final readonly class PageDirectory
{
    /**
     * @param array<class-string<MiddlewareInterface>|MiddlewareInterface> $middlewares
     */
    public function __construct(
        public string $directory,
        public string $pathPrefix = "",
        public array $middlewares = []
    ) {
    }
}
