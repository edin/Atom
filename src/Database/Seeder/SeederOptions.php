<?php

declare(strict_types=1);

namespace Atom\Database\Seeder;

final readonly class SeederOptions
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
