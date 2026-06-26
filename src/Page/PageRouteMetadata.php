<?php

declare(strict_types=1);

namespace Atom\Page;

final readonly class PageRouteMetadata
{
    /**
     * @param class-string<Page> $pageClass
     */
    public function __construct(public string $pageClass)
    {
    }
}
