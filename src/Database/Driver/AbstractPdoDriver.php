<?php

declare(strict_types=1);

namespace Atom\Database\Driver;

use Atom\Database\DatabaseDriver;
use PDO;

abstract class AbstractPdoDriver implements DatabaseDriver
{
    /**
     * @param array<int, mixed> $options
     */
    public function __construct(
        protected readonly string $dsn,
        protected readonly ?string $username = null,
        protected readonly ?string $password = null,
        protected readonly array $options = []
    ) {
    }

    public function connect(): PDO
    {
        $pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    public function dsn(): string
    {
        return $this->dsn;
    }

    public function username(): ?string
    {
        return $this->username;
    }

    public function password(): ?string
    {
        return $this->password;
    }

    /**
     * @return array<int, mixed>
     */
    public function options(): array
    {
        return $this->options;
    }
}
