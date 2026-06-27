<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Config\Env;

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
        return new self(
            driver: Env::string("DB_DRIVER", "sqlite"),
            database: Env::string("DB_DATABASE", "storage/app.sqlite"),
            host: Env::string("DB_HOST", "localhost"),
            port: self::optionalInt("DB_PORT"),
            username: self::optionalString("DB_USERNAME"),
            password: self::optionalString("DB_PASSWORD"),
            charset: Env::string("DB_CHARSET", "utf8mb4")
        );
    }

    private static function optionalString(string $key): ?string
    {
        $value = Env::get($key);

        return $value === null || trim($value) === "" ? null : $value;
    }

    private static function optionalInt(string $key): ?int
    {
        $value = Env::get($key);

        return $value === null || trim($value) === "" ? null : (int) $value;
    }
}
