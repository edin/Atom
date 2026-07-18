<?php

declare(strict_types=1);

namespace Atom\Publish;

final readonly class PublishResult
{
    /**
     * @param string[] $published
     * @param string[] $skipped
     * @param string[] $overwritten
     */
    public function __construct(
        public string $bundle,
        public array $published = [],
        public array $skipped = [],
        public array $overwritten = []
    ) {
    }

    public function changed(): bool
    {
        return $this->published !== [] || $this->overwritten !== [];
    }
}
