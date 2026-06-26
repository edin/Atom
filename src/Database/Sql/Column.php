<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final readonly class Column
{
    private function __construct(
        public string $name,
        public ?string $table = null,
        public ?string $alias = null
    ) {
    }

    public static function from(string $value): self
    {
        $parts = preg_split('/\s+/', trim($value), 2);
        $name = $parts[0] ?? "";
        $alias = $parts[1] ?? null;

        $nameParts = explode(".", $name, 2);
        if (count($nameParts) === 2) {
            return new self($nameParts[1], $nameParts[0], $alias);
        }

        return new self($name, null, $alias);
    }
}
