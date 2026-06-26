<?php

declare(strict_types=1);

namespace Atom\Database\Orm;

final readonly class EntityMetadata
{
    /**
     * @param class-string $className
     * @param ColumnMetadata[] $columns
     * @param RelationMetadata[] $relations
     */
    public function __construct(
        public string $className,
        public string $tableName,
        public array $columns,
        public array $relations = []
    ) {
    }

    /**
     * @return ColumnMetadata[]
     */
    public function primaryKeys(): array
    {
        return array_values(array_filter($this->columns, fn(ColumnMetadata $column) => $column->primaryKey));
    }

    public function primaryKey(): ?ColumnMetadata
    {
        $keys = $this->primaryKeys();
        return count($keys) === 1 ? $keys[0] : null;
    }

    /**
     * @return ColumnMetadata[]
     */
    public function selectableColumns(): array
    {
        return array_values(array_filter($this->columns, fn(ColumnMetadata $column) => $column->select));
    }

    /**
     * @return ColumnMetadata[]
     */
    public function insertableColumns(): array
    {
        return array_values(array_filter($this->columns, fn(ColumnMetadata $column) => $column->insert));
    }

    /**
     * @return ColumnMetadata[]
     */
    public function updatableColumns(): array
    {
        return array_values(array_filter($this->columns, fn(ColumnMetadata $column) => $column->update));
    }

    public function column(string $name): ?ColumnMetadata
    {
        foreach ($this->columns as $column) {
            if ($column->propertyName === $name || $column->columnName === $name) {
                return $column;
            }
        }

        return null;
    }

    public function relation(string $name): ?RelationMetadata
    {
        foreach ($this->relations as $relation) {
            if ($relation->propertyName === $name) {
                return $relation;
            }
        }

        return null;
    }
}
