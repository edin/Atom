<?php

declare(strict_types=1);

namespace Atom\Database;

class Repository
{
    private Database $database;
    private string $entityType;

    public function __construct(Database $database, string $entityType)
    {
        $this->database = $database;
        $this->entityType = $entityType;
    }

    public function query()
    {
        //TODO: return
    }

    public function findById($id)
    {
        //TODO: find by primary key
    }

    public function findByAttributes(array $attributes)
    {
        //TODO: combine all attributes using and operator
    }

    public function save($entity)
    {
        //TODO: If primary key is set call update, else call insert
    }

    public function insert($entity)
    {
        //TODO: Ensure that primary key is not set
    }

    public function update($entity)
    {
        //TODO: Ensure that primary key is set
    }

    public function remove($entity)
    {
        //TODO: forward to removeById
    }

    public function removeById($id)
    {
        //TODO: Use query builder to build
    }
}
