<?php

declare(strict_types=1);

namespace Atom\Page;

final readonly class PageActionCall
{
    /**
     * @param array<int, mixed> $arguments
     */
    public function __construct(
        public string $name,
        public array $arguments = []
    ) {
    }
}
