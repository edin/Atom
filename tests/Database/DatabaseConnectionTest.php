<?php

declare(strict_types=1);

namespace Atom\Tests\Database;

use Atom\Database\DatabaseConnection;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Sql\Query;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseConnectionTest extends TestCase
{
    public function testRunsSqlQueriesEndToEnd(): void
    {
        $connection = new DatabaseConnection(SqliteDriver::memory());
        $connection->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, active INTEGER)");

        $inserted = $connection->execute(Query::insert("users")->values([
            "name" => "Edin",
            "active" => true,
        ]));

        $rows = $connection->all(
            Query::select("users")
                ->columns("id", "name")
                ->where("active", true)
        );

        $this->assertSame(1, $inserted);
        $this->assertSame([["id" => 1, "name" => "Edin"]], $rows);
    }

    public function testCanFetchFirstAndScalar(): void
    {
        $connection = new DatabaseConnection(SqliteDriver::memory());
        $connection->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $connection->execute(Query::insert("users")->values(["name" => "Edin"]));
        $connection->execute(Query::insert("users")->values(["name" => "Amar"]));

        $first = $connection->first(Query::select("users")->columns("name")->orderBy("id"));
        $count = $connection->scalar(Query::select("users")->count());

        $this->assertSame(["name" => "Edin"], $first);
        $this->assertSame(2, (int) $count);
    }

    public function testCanExecuteRawSqlWithParameters(): void
    {
        $connection = new DatabaseConnection(SqliteDriver::memory());
        $connection->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");

        $connection->execute(
            "INSERT INTO users (name) VALUES (:name)",
            [":name" => "Edin"]
        );

        $this->assertSame(["name" => "Edin"], $connection->first(
            "SELECT name FROM users WHERE name = :name",
            [":name" => "Edin"]
        ));
        $this->assertSame("Edin", $connection->scalar(
            "SELECT name FROM users WHERE name = :name",
            [":name" => "Edin"]
        ));
    }

    public function testTransactionRollsBackOnFailure(): void
    {
        $connection = new DatabaseConnection(SqliteDriver::memory());
        $connection->pdo()->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");

        try {
            $connection->transaction(function (DatabaseConnection $connection): void {
                $connection->execute(Query::insert("users")->values(["name" => "Edin"]));
                throw new RuntimeException("fail");
            });
        } catch (RuntimeException) {
        }

        $count = $connection->scalar(Query::select("users")->count());

        $this->assertSame(0, (int) $count);
    }
}
