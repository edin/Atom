<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Inspector;

final readonly class NullSchemaInspector implements SchemaInspectorInterface
{
    public function hasTable(string $table): bool
    {
        return false;
    }

    public function hasColumn(string $table, string $column): bool
    {
        return false;
    }

    public function columns(string $table): array
    {
        return [];
    }
}
