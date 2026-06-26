<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final readonly class Table
{
    private function __construct(public string $name, public ?string $alias = null)
    {
    }

    public static function from(string $value): self
    {
        $parts = preg_split('/\s+/', trim($value), 2);
        return new self($parts[0] ?? "", $parts[1] ?? null);
    }
}
