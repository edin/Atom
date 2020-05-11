<?php

declare(strict_types=1);

namespace Atom\Database;

class Repository
{
    public function __construct(Database $database)
    {
        $this->database = $database;
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
