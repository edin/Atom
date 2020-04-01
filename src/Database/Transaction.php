<?php

namespace Atom\Database;

use PDO;
use Atom\Database\Interfaces\ITransaction;

class Transaction implements ITransaction
{
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }
}
