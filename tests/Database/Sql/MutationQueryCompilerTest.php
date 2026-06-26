<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Sql;

use Atom\Database\Sql\Compiler\MySqlCompiler;
use Atom\Database\Sql\Op;
use Atom\Database\Sql\Query;
use PHPUnit\Framework\TestCase;

final class MutationQueryCompilerTest extends TestCase
{
    public function testCompilesInsertQuery(): void
    {
        $query = Query::insert("users")
            ->values([
                "name" => "Edin",
                "age" => 35,
            ]);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame("INSERT INTO `users` (`name`, `age`) VALUES (:p1, :p2)", $command->sql);
        $this->assertSame([":p1" => "Edin", ":p2" => 35], $command->parameters);
    }

    public function testCompilesUpdateQuery(): void
    {
        $query = Query::update("users")
            ->set([
                "name" => "Edin",
                "age" => 36,
            ])
            ->where("id", 2);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame("UPDATE `users` SET `name` = :p1, `age` = :p2 WHERE `id` = :p3", $command->sql);
        $this->assertSame([":p1" => "Edin", ":p2" => 36, ":p3" => 2], $command->parameters);
    }

    public function testCompilesDeleteQuery(): void
    {
        $query = Query::delete("users")
            ->where("id", Op::gte(10))
            ->limit(1);

        $command = (new MySqlCompiler())->compile($query);

        $this->assertSame("DELETE FROM `users` WHERE `id` >= :p1 LIMIT 1", $command->sql);
        $this->assertSame([":p1" => 10], $command->parameters);
    }
}
