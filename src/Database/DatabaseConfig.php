<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Config\Options;

#[Options(prefix: "DB_")]
final readonly class DatabaseConfig
{
    /**
     * @param array<int, mixed> $options
     */
    public function __construct(
        public string $driver = "sqlite",
        public string $database = "storage/app.sqlite",
        public string $host = "localhost",
        public ?int $port = null,
        public ?string $username = null,
        public ?string $password = null,
        public string $charset = "utf8mb4",
        public array $options = []
    ) {
    }

    public static function fromEnv(): self
    {
        return \Atom\Config\Config::fromEnv()->options(self::class);
    }
}
