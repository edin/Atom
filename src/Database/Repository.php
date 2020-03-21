<?php

namespace Atom\Database;

use Atom\Database\Query\Criteria;

class Repository
{
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function scopeActive(Criteria $scope)
    {
        $scope->where("active", 1);
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
