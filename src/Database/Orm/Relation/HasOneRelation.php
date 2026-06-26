<?php

declare(strict_types=1);

namespace Atom\Database\Orm\Relation;

use Atom\Database\Db;
use Atom\Database\Orm\ColumnMetadata;

final class HasOneRelation extends Relation
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
        $localKey = $parentMetadata->column($this->relation->localKey);
        if ($localKey === null) {
            throw new \RuntimeException("Local key '{$this->relation->localKey}' is not mapped on {$parentMetadata->className}.");
        }

        $localValues = $this->keyValues($models, $localKey);
        if ($localValues === []) {
            $this->fill($models, [], $localKey);
            return;
        }

        $relatedMetadata = $this->metadataFactory->for($this->relation->relatedClass);
        $foreignKey = $relatedMetadata->column($this->relation->foreignKey);
        if ($foreignKey === null) {
            throw new \RuntimeException("Foreign key '{$this->relation->foreignKey}' is not mapped on {$relatedMetadata->className}.");
        }

        $related = $db
            ->select($this->relation->relatedClass)
            ->where($foreignKey->columnName, $localValues)
            ->all();

        $indexed = $this->indexBy($related, $foreignKey);
        $this->fill($models, $indexed, $localKey);
    }

    /**
     * @param object[] $models
     * @return array<int, mixed>
     */
    private function keyValues(array $models, ColumnMetadata $key): array
    {
        $values = [];

        foreach ($models as $model) {
            $value = $key->getValue($model);
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
    private function fill(array $models, array $related, ColumnMetadata $localKey): void
    {
        foreach ($models as $model) {
            $value = $localKey->getValue($model);
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
