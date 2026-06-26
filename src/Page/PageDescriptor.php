<?php

declare(strict_types=1);

namespace Atom\Page;

final readonly class PageDescriptor
{
    /**
     * @param class-string<Page> $pageClass
     */
    public function __construct(
        public string $path,
        public string $pageClass,
        public ?string $name = null,
        public string|array $method = "GET"
    ) {
    }
}
