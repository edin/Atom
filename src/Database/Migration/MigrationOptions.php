<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

final readonly class MigrationOptions
{
    public function __construct(
        public string $directory = "",
        public string $namespace = ""
    ) {
    }

    public function hasSource(): bool
    {
        return $this->directory !== "";
    }
}
