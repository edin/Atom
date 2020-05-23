<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Mapping\Mapping;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;

class Repository
{
    private Database $database;
    private string $entityType;
    private QueryBuilder $queryBuilder;

    public function __construct(Database $database, string $entityType)
    {
        $this->database = $database;
        $this->entityType = $entityType;
        $this->queryBuilder = new QueryBuilder($this->getMapping());
    }

    public function getMapping(): Mapping
    {
        //TODO: Use entity mapping if entity has defined getMapping() method
        //TODO: Use reflection to derive mapping
        $mapping = new Mapping();
        return $mapping;
    }

    public function query()
    {
        $query = $this->queryBuilder->getSelectQuery();
        $query->setConnection($this->database->getReadConnection());
        return $query;
    }

    public function findById($id)
    {
        $query = $this->queryBuilder->getSelectByPrimaryKey($id);
    }

    public function findByAttributes(array $attributes)
    {
        //TODO: combine all attributes using and operator
        $query = $this->queryBuilder->getSelectQuery();
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }
        $query->setConnection($this->database->getReadConnection());
    }

    public function save($entity)
    {
        if (!is_object($entity)) {
            throw new InvalidArgumentException("Parameter entity must an object");
        }
        $reflection = new ReflectionClass($entity);
        if ($reflection->getName() == $this->entityType) {
            throw new InvalidArgumentException("Invalid entity type, expected type is {$this->entityType}.");
        }
    }

    public function insert($entity)
    {
        $query = $this->queryBuilder->getInsertQuery($entity);
        $query->setConnection($this->database->getWriteConnection());
        $query->execute();
    }

    public function update($entity)
    {
        $query = $this->queryBuilder->getUpdateQuery($entity);
        $query->setConnection($this->database->getWriteConnection());
        $query->execute();
    }

    public function remove($entity)
    {
        $query = $this->queryBuilder->getDeleteQuery($entity);
        $query->setConnection($this->database->getWriteConnection());
        $query->execute();
    }

    public function removeById($id)
    {
        $query = $this->queryBuilder->getDeleteQueryByPk($id);
        $query->setConnection($this->database->getWriteConnection());
        $query->execute();
    }
}
