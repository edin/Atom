<?php

declare(strict_types=1);

namespace Atom\Database\Orm;

use DateTimeImmutable;
use ReflectionClass;

final readonly class RowHydrator
{
    public function __construct(private EntityMetadataFactory $metadataFactory = new EntityMetadataFactory())
    {
    }

    public function metadataFactory(): EntityMetadataFactory
    {
        return $this->metadataFactory;
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $row
     */
    public function hydrate(string $className, array $row): object
    {
        $metadata = $this->metadataFactory->for($className);
        $entity = (new ReflectionClass($className))->newInstanceWithoutConstructor();

        foreach ($metadata->columns as $column) {
            if (!array_key_exists($column->columnName, $row)) {
                continue;
            }

            $column->setValue($entity, $this->coerce($row[$column->columnName], $column));
        }

        return $entity;
    }

    private function coerce(mixed $value, ColumnMetadata $column): mixed
    {
        if ($value === null || ($value === "" && $column->allowsNull)) {
            return null;
        }

        return match ($column->propertyType) {
            "int" => (int) $value,
            "float" => (float) $value,
            "bool" => (bool) $value,
            "string" => (string) $value,
            DateTimeImmutable::class => $value instanceof DateTimeImmutable ? $value : new DateTimeImmutable((string) $value),
            default => $value,
        };
    }
}
