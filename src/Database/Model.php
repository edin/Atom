<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Orm\EntityMetadata;
use Atom\Database\Orm\EntityMetadataFactory;
use RuntimeException;

abstract class Model
{
    private static ?Db $db = null;

    public static function useDb(Db $db): void
    {
        self::$db = $db;
    }

    public static function query(): DbSelect
    {
        return self::db()->select(static::class);
    }

    /**
     * @return list<static>
     */
    public static function all(): array
    {
        /** @var list<static> $models */
        $models = static::query()->all();

        return $models;
    }

    public static function find(mixed $id): ?static
    {
        $primaryKey = self::metadata()->primaryKey()
            ?? throw new RuntimeException("Model " . static::class . " must define a single primary key.");

        $model = static::query()
            ->where($primaryKey->columnName, $id)
            ->first();

        return $model instanceof static ? $model : null;
    }

    public static function count(): int
    {
        return static::query()->total();
    }

    public function save(): int
    {
        return self::db()->save($this);
    }

    public function delete(): int
    {
        return self::db()->delete($this);
    }

    protected static function db(): Db
    {
        return self::$db ?? throw new RuntimeException("No database has been configured for models.");
    }

    private static function metadata(): EntityMetadata
    {
        return (new EntityMetadataFactory())->for(static::class);
    }
}
