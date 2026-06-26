<?php

declare(strict_types=1);

namespace Atom\Database\Orm;

use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\BelongsTo;
use Atom\Database\Orm\Attributes\HasMany;
use Atom\Database\Orm\Attributes\HasOne;
use Atom\Database\Orm\Attributes\Table;
use ReflectionAttribute;
use ReflectionClass;

final class EntityMetadataFactory
{
    /** @var array<class-string, EntityMetadata> */
    private array $cache = [];

    /**
     * @param class-string $className
     */
    public function for(string $className): EntityMetadata
    {
        return $this->cache[$className] ??= $this->build($className);
    }

    /**
     * @param class-string $className
     */
    private function build(string $className): EntityMetadata
    {
        $class = new ReflectionClass($className);
        $table = $class->getAttributes(Table::class)[0] ?? null;
        $tableName = $table?->newInstance()->name ?? $this->defaultTableName($class->getShortName());
        $columns = [];
        $relations = [];

        foreach ($class->getProperties() as $property) {
            $attribute = $property->getAttributes(Column::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
            if ($attribute !== null) {
                $property->setAccessible(true);
                $columns[] = ColumnMetadata::fromProperty($property, $attribute->newInstance());
            }

            $relation = $property->getAttributes(BelongsTo::class)[0] ?? null;
            if ($relation !== null) {
                $property->setAccessible(true);
                $relations[] = RelationMetadata::belongsTo($property, $relation->newInstance());
            }

            $relation = $property->getAttributes(HasOne::class)[0] ?? null;
            if ($relation !== null) {
                $property->setAccessible(true);
                $relations[] = RelationMetadata::hasOne($property, $relation->newInstance());
            }

            $relation = $property->getAttributes(HasMany::class)[0] ?? null;
            if ($relation !== null) {
                $property->setAccessible(true);
                $relations[] = RelationMetadata::hasMany($property, $relation->newInstance());
            }
        }

        return new EntityMetadata($className, $tableName, $columns, $relations);
    }

    private function defaultTableName(string $className): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }
}
