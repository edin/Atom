<?php

declare(strict_types=1);

namespace Atom\Database\Relation;

final class HasManyTroughRelation extends Relation
{
    private string $junctionTable;
    private string $relatedForeignKey;

    public function __construct(string $modelClass, string $relatedModelClass, string $junctionTable, string $foreignKey, string $relatedForeignKey)
    {
        parent::__construct($modelClass, $relatedModelClass, $foreignKey);
        $this->junctionTable = $junctionTable;
        $this->relatedForeignKey = $relatedForeignKey;
    }

    public function createQuery()
    {
    }
}
