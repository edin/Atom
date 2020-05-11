<?php

declare(strict_types=1);

namespace Atom\Database\Connector;

use PDO;
use Atom\Database\Interfaces\IDatabaseConnector;

abstract class AbstractConnector implements IDatabaseConnector
{
    protected $host;
    protected $port;
    protected $user;
    protected $password;
    protected $database;
    protected $attributes;
    protected $connection;

    public function __construct(string $host, string $user, string $password, string $database, ?int $port = null, array $attributes = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->attributes = $attributes;
    }

    abstract public function open(): PDO;

    public function close(): void
    {
        $this->connection = null;
    }

    public function isActive(): bool
    {
        return $this->connection !== null;
    }
}
