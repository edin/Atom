<?php

declare(strict_types=1);

namespace Atom\Database\Relation;

abstract class Relation
{
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
