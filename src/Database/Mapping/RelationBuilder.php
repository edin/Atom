<?php

declare(strict_types=1);

namespace Atom\Database\Mapping;

use Atom\Database\Relation\BelongsToRelation;
use Atom\Database\Relation\HasManyRelation;
use Atom\Database\Relation\HasManyTroughRelation;
use Atom\Database\Relation\HasOneRelation;

final class RelationBuilder
{
    private Mapping $mapping;
    private string $modelClass;
    private string $relationName;

    public function __construct(Mapping $mapping, string $relationName)
    {
        $this->mapping = $mapping;
        $this->relationName = $relationName;
        $this->modelClass = $mapping->getEntityClass();
    }

    public function hasMany(string $relatedModelClass, string $foreignKey): HasManyRelation
    {
        $relation = new HasManyRelation($this->modelClass, $relatedModelClass, $foreignKey);
        $this->mapping->addRelation($this->relationName, $relation);
        return $relation;
    }

    public function hasManyTrough(
        string $relatedModelClass,
        string $junctionTable,
        string $foreignKey,
        string $relatedForeignKey
    ): HasManyTroughRelation {

        $relation =  new HasManyTroughRelation(
            $this->modelClass,
            $relatedModelClass,
            $junctionTable,
            $foreignKey,
            $relatedForeignKey
        );

        $this->mapping->addRelation($this->relationName, $relation);
        return $relation;
    }

    public function hasOne(string $relatedModelClass, string $foreignKey): HasOneRelation
    {
        $relation =  new HasOneRelation($this->modelClass, $relatedModelClass, $foreignKey);
        $this->mapping->addRelation($this->relationName, $relation);
        return $relation;
    }

    public function belongsTo(string $relatedModelClass, string $foreignKey): BelongsToRelation
    {
        $relation =  new BelongsToRelation($this->modelClass, $relatedModelClass, $foreignKey);
        $this->mapping->addRelation($this->relationName, $relation);
        return $relation;
    }
}
