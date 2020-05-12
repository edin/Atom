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

    public function findById($id)
    {
    }

    public function findByAttributes(array $attributes)
    {
    }

    public function save($entity)
    {
    }

    public function insert($entity)
    {
    }

    public function update($entity)
    {
    }

    public function remove($entity)
    {
    }

    public function removeById($id)
    {
    }
}
