<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Mapping\Mapping;
use Atom\Database\Mapping\MappingHydrator;
use Atom\Database\Query\SelectQuery;
use Atom\Hydrator\IHydrator;
use InvalidArgumentException;
use ReflectionClass;

class Repository
{
    private Database $database;
    protected string $entityType;
    private QueryBuilder $queryBuilder;
    private IHydrator $hydrator;
    private Mapping $mapping;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->mapping = $this->getEntityMapping();
        $this->queryBuilder = new QueryBuilder($this->mapping);
        $this->hydrator = new MappingHydrator($this->entityType, $this->mapping);
    }

    protected function getEntityMapping()
    {
        $entity = new $this->entityType;
        return $entity->getMapping();
    }

    public function getMapping(): Mapping
    {
        return $this->mapping;
    }

    public function getHydrator(): IHydrator
    {
        return $this->hydrator;
    }

    public function query(): SelectQuery
    {
        $query = $this->queryBuilder->getSelectQuery();
        $query->setConnection($this->database->getReadConnection());
        $query->setHydrator($this->getHydrator());
        return $query;
    }

    public function findAll(): EntityCollection
    {
        return $this->query()->findAll();
    }

    public function findById($id)
    {
        $query = $this->queryBuilder->getSelectByPrimaryKey($id);
        $query->setConnection($this->database->getReadConnection());
        $query->setHydrator($this->getHydrator());
        return $query->findAll()->first();
    }

    public function findByAttributes(array $attributes)
    {
        $query = $this->queryBuilder->getSelectQuery();
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }
        $query->setConnection($this->database->getReadConnection());
        $query->setHydrator($this->getHydrator());

        return $query->findAll();
    }

    public function findOneByAttributes(array $attributes)
    {
        $query = $this->queryBuilder->getSelectQuery();
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }
        $query->setConnection($this->database->getReadConnection());
        $query->setHydrator($this->getHydrator());
        $query->limit(1);

        return $query->findAll()->first();
    }

    private function ensureEntityType($entity)
    {
        if (!is_object($entity)) {
            throw new InvalidArgumentException("Parameter entity must an object");
        }
        $reflection = new ReflectionClass($entity);
        if ($reflection->getName() != $this->entityType) {
            throw new InvalidArgumentException("Invalid entity type, expected type is {$this->entityType}.");
        }
    }

    public function save($entity)
    {
        $this->ensureEntityType($entity);
        $primaryKey = $this->mapping->getPrimaryKeyValueOrNull($entity);
        if (!empty($primaryKey)) {
            $this->update($entity);
        } else {
            $this->insert($entity);
        }
        return $entity;
    }

    public function insert($entity)
    {
        $this->ensureEntityType($entity);
        //NOTE: This may work slow if inserting multiple entitites in loop
        //      get insert query is designed to build query that can be reused multiple times
        //      but here is generated always

        $query = $this->queryBuilder->getInsertQuery($entity);
        $query->setConnection($this->database->getWriteConnection());
        $query->setHydrator($this->getHydrator());
        $command = $query->compileQuery();

        $command->execute();
        $id = $command->getLastInsertId();
        $this->mapping->setPrimaryKeyValueOrNull($entity, $id);
    }

    public function update($entity)
    {
        $this->ensureEntityType($entity);

        $query = $this->queryBuilder->getUpdateQuery($entity);
        $query->setConnection($this->database->getWriteConnection());
        $query->setHydrator($this->getHydrator());
        $query->execute();
    }

    public function remove($entity)
    {
        $this->ensureEntityType($entity);

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
