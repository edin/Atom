<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Orm\RowHydrator;
use Atom\Database\Orm\ColumnMetadata;
use Atom\Database\Orm\ColumnValueProvider;
use Atom\Database\Sql\Command;
use Atom\Database\Sql\Query;
use Atom\Database\Sql\SqlQuery;
use DateTimeInterface;
use RuntimeException;

final readonly class Db
{
    public function __construct(
        private DatabaseConnection $connection,
        private RowHydrator $hydrator = new RowHydrator()
    ) {
    }

    public function connection(): DatabaseConnection
    {
        return $this->connection;
    }

    public function compile(SqlQuery $query): Command
    {
        return $this->connection->compile($query);
    }

    public function select(string $tableOrClass): DbSelect
    {
        if (class_exists($tableOrClass)) {
            $metadata = $this->hydrator->metadataFactory()->for($tableOrClass);
            $select = Query::select($metadata->tableName);

            foreach ($metadata->selectableColumns() as $column) {
                $select->columns($column->columnName);
            }

            return new DbSelect($this, $select, $tableOrClass);
        }

        return new DbSelect($this, Query::select($tableOrClass));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function execute(string|SqlQuery|Command $query, array $parameters = []): int
    {
        return $this->connection->execute($query, $parameters);
    }

    public function insert(object $entity): int
    {
        $metadata = $this->hydrator->metadataFactory()->for($entity::class);
        $values = [];
        $autoIncrementKey = null;

        foreach ($metadata->insertableColumns() as $column) {
            $values[$column->columnName] = $this->toDatabaseValue($this->valueForInsert($entity, $column));
        }

        foreach ($metadata->primaryKeys() as $key) {
            if ($key->autoIncrement) {
                $autoIncrementKey = $key;
                break;
            }
        }

        $affected = $this->execute(Query::insert($metadata->tableName)->values($values));

        if ($autoIncrementKey !== null) {
            $id = $this->connection->lastInsertId();
            if ($id !== false) {
                $autoIncrementKey->setValue($entity, $this->coercePrimaryKey($id, $autoIncrementKey->propertyType));
            }
        }

        return $affected;
    }

    public function update(object $entity): int
    {
        $metadata = $this->hydrator->metadataFactory()->for($entity::class);
        $primaryKey = $metadata->primaryKey() ?? throw new RuntimeException("Entity {$metadata->className} must define a single primary key.");
        $values = [];

        foreach ($metadata->updatableColumns() as $column) {
            $values[$column->columnName] = $this->toDatabaseValue($this->valueForUpdate($entity, $column));
        }

        if ($values === []) {
            return 0;
        }

        return $this->execute(
            Query::update($metadata->tableName)
                ->set($values)
                ->where($primaryKey->columnName, $primaryKey->getValue($entity))
        );
    }

    public function save(object $entity): int
    {
        $metadata = $this->hydrator->metadataFactory()->for($entity::class);
        $primaryKey = $metadata->primaryKey() ?? throw new RuntimeException("Entity {$metadata->className} must define a single primary key.");
        $value = $primaryKey->getValueOrNull($entity);

        return $this->isEmptyPrimaryKey($value) ? $this->insert($entity) : $this->update($entity);
    }

    public function delete(object $entity): int
    {
        $metadata = $this->hydrator->metadataFactory()->for($entity::class);
        $primaryKey = $metadata->primaryKey() ?? throw new RuntimeException("Entity {$metadata->className} must define a single primary key.");

        return $this->execute(
            Query::delete($metadata->tableName)
                ->where($primaryKey->columnName, $primaryKey->getValue($entity))
        );
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<int, array<string, mixed>>
     */
    public function all(string|SqlQuery|Command $query, array $parameters = []): array
    {
        return $this->connection->all($query, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>|null
     */
    public function first(string|SqlQuery|Command $query, array $parameters = []): ?array
    {
        return $this->connection->first($query, $parameters);
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $parameters
     */
    public function firstAs(string $className, string|SqlQuery|Command $query, array $parameters = []): ?object
    {
        $row = $this->first($query, $parameters);
        return $row === null ? null : $this->hydrator->hydrate($className, $row);
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $parameters
     * @return object[]
     */
    public function allAs(string $className, string|SqlQuery|Command $query, array $parameters = []): array
    {
        return array_map(
            fn(array $row): object => $this->hydrator->hydrate($className, $row),
            $this->all($query, $parameters)
        );
    }

    public function load(object $entity, string ...$relations): object
    {
        $this->loadMany([$entity], ...$relations);
        return $entity;
    }

    /**
     * @param object[] $entities
     * @return object[]
     */
    public function loadMany(array $entities, string ...$relations): array
    {
        if ($entities === []) {
            return $entities;
        }

        $metadata = $this->hydrator->metadataFactory()->for($entities[0]::class);

        foreach ($relations as $relationName) {
            $relation = $metadata->relation($relationName);
            if ($relation === null) {
                throw new \RuntimeException("Relation '{$relationName}' is not defined on {$metadata->className}.");
            }

            $relation->createRelation($this->hydrator->metadataFactory())->load($this, $entities);
        }

        return $entities;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function scalar(string|SqlQuery|Command $query, array $parameters = []): mixed
    {
        return $this->connection->scalar($query, $parameters);
    }

    public function transaction(callable $callback): mixed
    {
        return $this->connection->transaction(fn() => $callback($this));
    }

    private function isEmptyPrimaryKey(mixed $value): bool
    {
        return $value === null || $value === "" || $value === 0 || $value === "0";
    }

    private function coercePrimaryKey(string $value, string $type): mixed
    {
        return match ($type) {
            "int" => (int) $value,
            "float" => (float) $value,
            default => $value,
        };
    }

    private function valueForInsert(object $entity, ColumnMetadata $column): mixed
    {
        return $column->onInsert === null
            ? $column->getValue($entity)
            : $this->provideValue($entity, $column, $column->onInsert);
    }

    private function valueForUpdate(object $entity, ColumnMetadata $column): mixed
    {
        return $column->onUpdate === null
            ? $column->getValue($entity)
            : $this->provideValue($entity, $column, $column->onUpdate);
    }

    /**
     * @param class-string<ColumnValueProvider> $providerClass
     */
    private function provideValue(object $entity, ColumnMetadata $column, string $providerClass): mixed
    {
        $provider = new $providerClass();
        if (!$provider instanceof ColumnValueProvider) {
            throw new RuntimeException("Column value provider '{$providerClass}' must implement ColumnValueProvider.");
        }

        $value = $provider->value($entity, $column);
        $column->setValue($entity, $value);

        return $value;
    }

    private function toDatabaseValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format("Y-m-d H:i:s");
        }

        return $value;
    }
}
