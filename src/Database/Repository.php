<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Mapping\Mapping;
use Atom\Database\Query\SelectQuery;
use Atom\Hydrator\IHydrator;
use Atom\Hydrator\PropertyHydrator;
use InvalidArgumentException;
use ReflectionClass;

class Repository
{
    private Database $database;
    private string $entityType;
    private QueryBuilder $queryBuilder;
    private IHydrator $hydrator;

    public function __construct(Database $database, string $entityType)
    {
        $this->database = $database;
        $this->entityType = $entityType;
        $this->queryBuilder = new QueryBuilder($this->getMapping());
        $this->hydrator = new PropertyHydrator($entityType);
    }

    public function getMapping(): Mapping
    {
        $entity = new $this->entityType;
        return $entity->getMapping();
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

    public function findAll()
    {
        $items =  $this->query()->queryAll();
        $result = [];
        foreach ($items as $item) {
            $result[] = $this->hydrator->hydrate($item);
        }
        return $result;
    }

    public function findById($id)
    {
        $query = $this->queryBuilder->getSelectByPrimaryKey($id);
        $query->setConnection($this->database->getReadConnection());
        $query->setHydrator($this->getHydrator());
    }

    public function findByAttributes(array $attributes)
    {
        $query = $this->queryBuilder->getSelectQuery();
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }
        $query->setConnection($this->database->getReadConnection());
        $query->setHydrator($this->getHydrator());
    }

    private function ensureEntityType($entity)
    {
        if (!is_object($entity)) {
            throw new InvalidArgumentException("Parameter entity must an object");
        }
        $reflection = new ReflectionClass($entity);
        if ($reflection->getName() == $this->entityType) {
            throw new InvalidArgumentException("Invalid entity type, expected type is {$this->entityType}.");
        }
    }

    public function save($entity)
    {
        $this->ensureEntityType($entity);

        //TODO: Call insert or save
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
        $query->execute();
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
