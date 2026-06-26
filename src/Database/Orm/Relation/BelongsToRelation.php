<?php

declare(strict_types=1);

namespace Atom\Database\Orm\Relation;

use Atom\Database\Db;
use Atom\Database\Orm\ColumnMetadata;

final class BelongsToRelation extends Relation
{
    /**
     * @param object[] $models
     */
    public function load(Db $db, array $models): void
    {
        if ($models === []) {
            return;
        }

        $parentMetadata = $this->metadataFactory->for($models[0]::class);
        $foreignKey = $parentMetadata->column($this->relation->foreignKey);
        if ($foreignKey === null) {
            throw new \RuntimeException("Foreign key '{$this->relation->foreignKey}' is not mapped on {$parentMetadata->className}.");
        }

        $foreignValues = $this->foreignValues($models, $foreignKey);
        if ($foreignValues === []) {
            $this->fill($models, []);
            return;
        }

        $relatedMetadata = $this->metadataFactory->for($this->relation->relatedClass);
        $ownerKey = $relatedMetadata->column($this->relation->ownerKey);
        if ($ownerKey === null) {
            throw new \RuntimeException("Owner key '{$this->relation->ownerKey}' is not mapped on {$relatedMetadata->className}.");
        }

        $related = $db
            ->select($this->relation->relatedClass)
            ->where($ownerKey->columnName, $foreignValues)
            ->all();

        $indexed = $this->indexBy($related, $ownerKey);
        $this->fill($models, $indexed, $foreignKey);
    }

    /**
     * @param object[] $models
     * @return array<int, mixed>
     */
    private function foreignValues(array $models, ColumnMetadata $foreignKey): array
    {
        $values = [];

        foreach ($models as $model) {
            $value = $foreignKey->getValue($model);
            if ($value !== null) {
                $values[(string) $value] = $value;
            }
        }

        return array_values($values);
    }

    /**
     * @param object[] $models
     * @param array<string, object> $related
     */
    private function fill(array $models, array $related, ?ColumnMetadata $foreignKey = null): void
    {
        foreach ($models as $model) {
            $value = $foreignKey?->getValue($model);
            $this->relation->setValue($model, $value === null ? null : ($related[(string) $value] ?? null));
        }
    }

    /**
     * @param object[] $models
     * @return array<string, object>
     */
    private function indexBy(array $models, ColumnMetadata $key): array
    {
        $indexed = [];

        foreach ($models as $model) {
            $indexed[(string) $key->getValue($model)] = $model;
        }

        return $indexed;
    }
}
