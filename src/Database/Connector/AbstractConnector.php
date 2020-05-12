<?php

declare(strict_types=1);

namespace Atom\Database\Connector;

use PDO;
use Atom\Database\Interfaces\IDatabaseConnector;

abstract class AbstractConnector implements IDatabaseConnector
{
    protected string $host;
    protected ?int   $port;
    protected string $user;
    protected string $password;
    protected string $database;
    protected array  $attributes;
    protected ?PDO $connection;

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
