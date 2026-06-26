<?php

declare(strict_types=1);

namespace Atom\Database\Orm;

use Atom\Database\Orm\Attributes\Column;
use Error;
use ReflectionNamedType;
use ReflectionProperty;

final readonly class ColumnMetadata
{
    public function __construct(
        public string $propertyName,
        public string $columnName,
        public string $propertyType,
        public bool $allowsNull,
        public bool $select,
        public bool $insert,
        public bool $update,
        public bool $primaryKey,
        public bool $autoIncrement,
        public ?string $converter,
        public ?string $onInsert,
        public ?string $onUpdate,
        private ReflectionProperty $property
    ) {
    }

    public static function fromProperty(ReflectionProperty $property, Column $column): self
    {
        $type = $property->getType();
        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : "mixed";

        return new self(
            propertyName: $property->getName(),
            columnName: $column->name ?? $property->getName(),
            propertyType: $typeName,
            allowsNull: $type?->allowsNull() ?? true,
            select: $column->select,
            insert: $column->insert,
            update: $column->update,
            primaryKey: $column instanceof Attributes\PrimaryKey,
            autoIncrement: $column instanceof Attributes\PrimaryKey && $column->autoIncrement,
            converter: $column->converter,
            onInsert: $column->onInsert,
            onUpdate: $column->onUpdate,
            property: $property
        );
    }

    public function setValue(object $entity, mixed $value): void
    {
        $this->property->setValue($entity, $value);
    }

    public function getValue(object $entity): mixed
    {
        return $this->property->getValue($entity);
    }

    public function getValueOrNull(object $entity): mixed
    {
        try {
            return $this->property->getValue($entity);
        } catch (Error) {
            return null;
        }
    }
}
