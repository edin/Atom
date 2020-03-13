<?php

namespace Atom\Tests\Container\Types;

class Repository implements IRepository
{
    public $database;

    public function __construct(IDatabase $database)
    {
        $this->database = $database;
    }
}