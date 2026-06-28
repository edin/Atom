<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Sql;

use Atom\Database\Sql\Compiler\PostgresCompiler;
use Atom\Database\Sql\Op;
use Atom\Database\Sql\Query;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PostgresCompilerTest extends TestCase
{
    public function testCompilesSelectWithDoubleQuotedIdentifiers(): void
    {
        $query = Query::select("users u")
            ->columns("u.id", "u.name")
            ->where("u.age", Op::gte(18));

        $command = (new PostgresCompiler())->compile($query);

        $this->assertSame(
            'SELECT "u"."id", "u"."name" FROM "users" "u" WHERE "u"."age" >= :p1',
            $command->sql
        );
        $this->assertSame([":p1" => 18], $command->parameters);
    }

    public function testCompilesLimitAndOffset(): void
    {
        $query = Query::select("users")
            ->columns("id", "name")
            ->orderBy("name")
            ->offset(20)
            ->limit(10);

        $command = (new PostgresCompiler())->compile($query);

        $this->assertSame(
            'SELECT "id", "name" FROM "users" ORDER BY "name" ASC LIMIT 10 OFFSET 20',
            $command->sql
        );
    }

    public function testCompilesJoinsAndWhereExpressions(): void
    {
        $query = Query::select("users u")
            ->columns("u.id", "p.name profileName")
            ->leftJoin("profiles p on p.user_id = u.id")
            ->whereExp("u.active = :active and p.name is not null", ["active" => true]);

        $command = (new PostgresCompiler())->compile($query);

        $this->assertSame(
            'SELECT "u"."id", "p"."name" "profileName" FROM "users" "u" LEFT JOIN "profiles" "p" ON "p"."user_id" = "u"."id" WHERE "u"."active" = :active AND "p"."name" IS NOT NULL',
            $command->sql
        );
        $this->assertSame([":active" => true], $command->parameters);
    }

    public function testCompilesInsertUpdateAndDelete(): void
    {
        $compiler = new PostgresCompiler();

        $insert = $compiler->compile(Query::insert("users")->values([
            "name" => "Ada",
            "age" => 36,
        ]));
        $update = $compiler->compile(Query::update("users")->set([
            "name" => "Ada",
        ])->where("id", 1));
        $delete = $compiler->compile(Query::delete("users")->where("id", 1));

        $this->assertSame('INSERT INTO "users" ("name", "age") VALUES (:p1, :p2)', $insert->sql);
        $this->assertSame('UPDATE "users" SET "name" = :p1 WHERE "id" = :p2', $update->sql);
        $this->assertSame('DELETE FROM "users" WHERE "id" = :p1', $delete->sql);
    }

    public function testThrowsForDeleteLimit(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("PostgreSQL does not support DELETE LIMIT.");

        (new PostgresCompiler())->compile(Query::delete("users")->where("id", 1)->limit(1));
    }
}
