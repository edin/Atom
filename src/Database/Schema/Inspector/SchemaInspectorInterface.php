<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Inspector;

interface SchemaInspectorInterface
{
    public function hasTable(string $table): bool;

    public function hasColumn(string $table, string $column): bool;

    /**
     * @return string[]
     */
    public function columns(string $table): array;
}
