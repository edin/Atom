<?php

declare(strict_types=1);

namespace Atom\Database\Relation;

abstract class Relation
{
    private string $relationName;
    protected string $modelClass;
    protected string $relatedModelClass;
    protected ?string $foreignKey = null;
    protected $criteria;

    public function __construct(string $modelClass, string $relatedModelClass, ?string $foreignKey = null)
    {
        $this->modelClass = $modelClass;
        $this->relatedModelClass = $relatedModelClass;
        $this->foreignKey = $foreignKey;
    }

    public function setRelationName(string $relationName): void
    {
        $this->relationName = $relationName;
    }

    public function getRelationName(): string
    {
        return $this->relationName;
    }

    public function withCriteria(callable $criteria)
    {
        $this->criteria = $criteria;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }

    public abstract function createQuery();
}
