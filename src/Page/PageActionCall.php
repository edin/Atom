<?php

declare(strict_types=1);

namespace Atom\Page;

final readonly class PageActionCall
{
    /**
     * @param array<int, mixed> $arguments
     * @param list<string> $targetPath
     */
    public function __construct(
        public string $name,
        public array $arguments = [],
        public array $targetPath = []
    ) {
    }

    public function fullName(): string
    {
        return implode(".", [...$this->targetPath, $this->name]);
    }
}
