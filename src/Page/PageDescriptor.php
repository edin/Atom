<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Http\MiddlewareInterface;

final readonly class PageDescriptor
{
    /**
     * @param class-string<Page> $pageClass
     * @param array<class-string<MiddlewareInterface>|MiddlewareInterface> $middlewares
     */
    public function __construct(
        public string $path,
        public string $pageClass,
        public ?string $name = null,
        public array $middlewares = []
    ) {
    }
}
