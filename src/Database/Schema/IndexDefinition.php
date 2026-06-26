<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

final readonly class IndexDefinition
{
    /**
     * @param string[] $columns
     */
    private function __construct(
        public array $columns,
        public bool $unique = false,
        public ?string $name = null
    ) {
    }

    /**
     * @param string[] $columns
     */
    public static function index(array $columns, ?string $name = null): self
    {
        return new self($columns, false, $name);
    }

    /**
     * @param string[] $columns
     */
    public static function unique(array $columns, ?string $name = null): self
    {
        return new self($columns, true, $name);
    }
}

