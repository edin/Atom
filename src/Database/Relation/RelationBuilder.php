<?php

declare(strict_types=1);

namespace Atom\Database\Relation;

final class RelationBuilder
{
    private string $modelClass;

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function hasMany(string $relatedModelClass, string $foreignKey): HasManyRelation
    {
        return new HasManyRelation($this->modelClass, $relatedModelClass, $foreignKey);
    }

    public function hasManyTrough(string $relatedModelClass, string $junctionTable, string $foreignKey, string $relatedForeignKey): HasManyTroughRelation
    {
        return new HasManyTroughRelation($this->modelClass, $relatedModelClass, $junctionTable, $foreignKey, $relatedForeignKey);
    }

    public function hasOne(string $relatedModelClass, string $foreignKey): HasOneRelation
    {
        return new HasOneRelation($this->modelClass, $relatedModelClass, $foreignKey);
    }

    public function belongsTo(string $relatedModelClass, string $foreignKey): BelongsToRelation
    {
        return new BelongsToRelation($this->modelClass, $relatedModelClass, $foreignKey);
    }
}
