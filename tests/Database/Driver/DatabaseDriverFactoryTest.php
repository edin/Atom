<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Driver;

use Atom\Config\Env;
use Atom\Config\Config;
use Atom\Database\DatabaseConfig;
use Atom\Database\DatabaseDriverFactory;
use Atom\Database\DatabaseDriverFactoryException;
use Atom\Database\Driver\MySqlDriver;
use Atom\Database\Driver\PostgresDriver;
use Atom\Database\Driver\SqliteDriver;
use PHPUnit\Framework\TestCase;

final class DatabaseDriverFactoryTest extends TestCase
{
    /** @var string[] */
    private array $keys = [
        "DB_DRIVER",
        "DB_DATABASE",
        "DB_HOST",
        "DB_PORT",
        "DB_USERNAME",
        "DB_PASSWORD",
        "DB_CHARSET",
    ];

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    public function testCreatesSqliteDriverFromRelativePath(): void
    {
        $driver = (new DatabaseDriverFactory("D:/app"))->create(new DatabaseConfig(
            driver: "sqlite",
            database: "storage/app.sqlite"
        ));

        $this->assertInstanceOf(SqliteDriver::class, $driver);
        $this->assertSame("sqlite:D:/app/storage/app.sqlite", $driver->dsn());
    }

    public function testCreatesSqliteMemoryDriverWithoutRootPrefix(): void
    {
        $driver = (new DatabaseDriverFactory("D:/app"))->create(new DatabaseConfig(
            driver: "sqlite",
            database: ":memory:"
        ));

        $this->assertInstanceOf(SqliteDriver::class, $driver);
        $this->assertSame("sqlite::memory:", $driver->dsn());
    }

    public function testCreatesMysqlDriver(): void
    {
        $driver = (new DatabaseDriverFactory())->create(new DatabaseConfig(
            driver: "mysql",
            database: "atom",
            host: "127.0.0.1",
            port: 3307,
            username: "root",
            password: "secret",
            charset: "utf8mb4"
        ));

        $this->assertInstanceOf(MySqlDriver::class, $driver);
        $this->assertSame("mysql:dbname=atom;host=127.0.0.1;charset=utf8mb4;port=3307", $driver->dsn());
        $this->assertSame("root", $driver->username());
        $this->assertSame("secret", $driver->password());
    }

    public function testCreatesConfigFromEnvironment(): void
    {
        Env::load($this->file(<<<'ENV'
            DB_DRIVER=mysql
            DB_DATABASE=atom
            DB_HOST=db
            DB_PORT=3306
            DB_USERNAME=atom
            DB_PASSWORD=secret
            DB_CHARSET=utf8
            ENV));

        $config = DatabaseConfig::fromEnv();

        $this->assertSame("mysql", $config->driver);
        $this->assertSame("atom", $config->database);
        $this->assertSame("db", $config->host);
        $this->assertSame(3306, $config->port);
        $this->assertSame("atom", $config->username);
        $this->assertSame("secret", $config->password);
        $this->assertSame("utf8", $config->charset);
    }

    public function testBlankOptionalEnvironmentValuesBecomeNull(): void
    {
        Env::load($this->file(<<<'ENV'
            DB_PORT=
            DB_USERNAME=
            DB_PASSWORD=
            ENV));

        $config = DatabaseConfig::fromEnv();

        $this->assertNull($config->port);
        $this->assertNull($config->username);
        $this->assertNull($config->password);
    }

    public function testCreatesConfigFromTypedOptions(): void
    {
        $config = Config::fromEnv([
            "DB_DRIVER" => "pgsql",
            "DB_DATABASE" => "atom",
            "DB_HOST" => "db",
            "DB_PORT" => "5432",
            "DB_USERNAME" => "",
            "DB_PASSWORD" => "",
        ])->options(DatabaseConfig::class);

        $this->assertSame("pgsql", $config->driver);
        $this->assertSame("atom", $config->database);
        $this->assertSame("db", $config->host);
        $this->assertSame(5432, $config->port);
        $this->assertNull($config->username);
        $this->assertNull($config->password);
    }

    public function testCreatesPostgresDriver(): void
    {
        $driver = (new DatabaseDriverFactory())->create(new DatabaseConfig(
            driver: "pgsql",
            database: "atom",
            host: "127.0.0.1",
            port: 5433,
            username: "postgres",
            password: "secret"
        ));

        $this->assertInstanceOf(PostgresDriver::class, $driver);
        $this->assertSame("pgsql:dbname=atom;host=127.0.0.1;port=5433", $driver->dsn());
        $this->assertSame("postgres", $driver->username());
        $this->assertSame("secret", $driver->password());
    }

    public function testThrowsForUnknownDriver(): void
    {
        $this->expectException(DatabaseDriverFactoryException::class);
        $this->expectExceptionMessage("Unsupported database driver 'sqlserver'.");

        (new DatabaseDriverFactory())->create(new DatabaseConfig(driver: "sqlserver"));
    }

    private function file(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), "atom_db_env_");
        $this->assertIsString($path);
        file_put_contents($path, $this->normalize($contents));

        return $path;
    }

    private function normalize(string $contents): string
    {
        $lines = explode("\n", $contents);
        $lines = array_map(static fn(string $line): string => preg_replace('/^\s{12}/', "", $line) ?? $line, $lines);

        return trim(implode("\n", $lines)) . "\n";
    }
}
