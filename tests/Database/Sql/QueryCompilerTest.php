<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Sql;

use Atom\Database\Sql\Compiler\MySqlCompiler;
use Atom\Database\Sql\Op;
use Atom\Database\Sql\Query;
use PHPUnit\Framework\TestCase;

final class QueryCompilerTest extends TestCase
{
    public function testCompilesSelectQuery(): void
    {
        $query = Query::select("users")
            ->columns("id", "name")
            ->where("age", Op::gte(18));

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame("SELECT `id`, `name` FROM `users` WHERE `age` >= :p1", $command->sql);
        $this->assertSame([":p1" => 18], $command->parameters);
    }

    public function testNormalizesCommonWhereValues(): void
    {
        $query = Query::select("users u")
            ->where("u.status", "active")
            ->where("u.id", [1, 2, 3])
            ->where("u.deleted_at", null);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT * FROM `users` `u` WHERE `u`.`status` = :p1 AND `u`.`id` IN (:p2, :p3, :p4) AND `u`.`deleted_at` IS NULL",
            $command->sql
        );
        $this->assertSame([":p1" => "active", ":p2" => 1, ":p3" => 2, ":p4" => 3], $command->parameters);
    }

    public function testCompilesOrderingAndLimit(): void
    {
        $query = Query::select("users")
            ->columns("id", "name")
            ->orderBy("name")
            ->orderByDesc("id")
            ->offset(20)
            ->limit(10);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT `id`, `name` FROM `users` ORDER BY `name` ASC, `id` DESC LIMIT 20, 10",
            $command->sql
        );
        $this->assertSame([], $command->parameters);
    }

    public function testCompilesGroupBy(): void
    {
        $query = Query::select("users u")
            ->columns("u.role", "u.status")
            ->groupBy("u.role", "u.status")
            ->orderBy("u.role");

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT `u`.`role`, `u`.`status` FROM `users` `u` GROUP BY `u`.`role`, `u`.`status` ORDER BY `u`.`role` ASC",
            $command->sql
        );
        $this->assertSame([], $command->parameters);
    }

    public function testCompilesCount(): void
    {
        $query = Query::select("users u")
            ->count("u.id", "total")
            ->where("u.active", true);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT COUNT(`u`.`id`) `total` FROM `users` `u` WHERE `u`.`active` = :p1",
            $command->sql
        );
        $this->assertSame([":p1" => true], $command->parameters);
    }

    public function testCompilesHaving(): void
    {
        $query = Query::select("users u")
            ->columns("u.role")
            ->groupBy("u.role")
            ->having("count", Op::gt(1))
            ->orHaving("u.role", "admin");

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT `u`.`role` FROM `users` `u` GROUP BY `u`.`role` HAVING `count` > :p1 OR `u`.`role` = :p2",
            $command->sql
        );
        $this->assertSame([":p1" => 1, ":p2" => "admin"], $command->parameters);
    }

    public function testCompilesGroupedWhereConditions(): void
    {
        $query = Query::select("users")
            ->where("status", "active")
            ->group(function ($where) {
                $where
                    ->where("age", Op::gte(18))
                    ->orWhere("role", "admin");
            });

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT * FROM `users` WHERE `status` = :p1 AND (`age` >= :p2 OR `role` = :p3)",
            $command->sql
        );
        $this->assertSame([":p1" => "active", ":p2" => 18, ":p3" => "admin"], $command->parameters);
    }

    public function testCompilesJoins(): void
    {
        $query = Query::select("users u")
            ->columns("u.id", "p.name profileName")
            ->leftJoin("profiles p", function ($join) {
                $join->on("p.user_id", Op::column("u.id"));
            })
            ->where("u.active", true);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT `u`.`id`, `p`.`name` `profileName` FROM `users` `u` LEFT JOIN `profiles` `p` ON `p`.`user_id` = `u`.`id` WHERE `u`.`active` = :p1",
            $command->sql
        );
        $this->assertSame([":p1" => true], $command->parameters);
    }

    public function testParsesJoinOnExpression(): void
    {
        $query = Query::select("users u")
            ->columns("u.id", "p.name profileName")
            ->leftJoin("profiles p on p.user_id = u.id")
            ->where("u.active", true);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT `u`.`id`, `p`.`name` `profileName` FROM `users` `u` LEFT JOIN `profiles` `p` ON `p`.`user_id` = `u`.`id` WHERE `u`.`active` = :p1",
            $command->sql
        );
        $this->assertSame([":p1" => true], $command->parameters);
    }

    public function testCompilesWhereExpression(): void
    {
        $query = Query::select("users u")
            ->whereExp("u.id = :id and u.id < 100", ["id" => 2]);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT * FROM `users` `u` WHERE `u`.`id` = :id AND `u`.`id` < 100",
            $command->sql
        );
        $this->assertSame([":id" => 2], $command->parameters);
    }

    public function testCompilesHavingExpression(): void
    {
        $query = Query::select("users u")
            ->columns("u.role")
            ->groupBy("u.role")
            ->havingExp("count > :count", ["count" => 1]);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame(
            "SELECT `u`.`role` FROM `users` `u` GROUP BY `u`.`role` HAVING `count` > :count",
            $command->sql
        );
        $this->assertSame([":count" => 1], $command->parameters);
    }
}
