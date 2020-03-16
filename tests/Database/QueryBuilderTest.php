<?php

namespace Atom\Tests\Database;

use Atom\Database\Query\Query;
use PHPUnit\Framework\TestCase;
use Atom\Database\Query\Criteria;
use Atom\Database\Query\Operator;
use Atom\Database\Query\Compilers\MySqlCompiler;

final class QueryBuilderTest extends TestCase
{
    private function clean($text)
    {
        $text = str_replace('\n', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function getSql($query): string
    {
        $compiler = new MySqlCompiler();
        $sql = $compiler->compileQuery($query);
        return $this->clean($sql);
    }

    private function assertQuery($expected, $query)
    {
        $compiler = new MySqlCompiler();
        $sql = $compiler->compileQuery($query);
        $sql = $this->clean($sql);
        $expected = $this->clean($expected);
        $this->assertEquals($expected, $sql);
    }

    public function testSimpleSelect(): void
    {
        $query = Query::select()->from("users u");
        $this->assertQuery("SELECT * FROM `users` `u`", $query);
    }

    public function testSimpleSelectWithColumns(): void
    {
        $query = Query::select()
                ->from("users u")
                ->columns(['u.id id', 'u.first_name firstName']);

        $this->assertQuery("SELECT `u`.`id` `id`, `u`.`first_name` `firstName` FROM `users` `u`", $query);
    }

    public function testSimpleSelectWithOrder(): void
    {
        $query = Query::select()->from("users u")->orderBy("u.first_name")->orderByDesc("u.last_name");
        $expected = "SELECT * FROM `users` `u` ORDER BY `u`.`first_name` ASC, `u`.`last_name` DESC";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithGroupBy(): void
    {
        $query = Query::select()->from("users u")->groupBy("u.first_name")->groupBy("u.last_name");
        $expected = "SELECT * FROM `users` `u` GROUP BY `u`.`first_name`, `u`.`last_name`";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithWhere(): void
    {
        $query = Query::select()->from("users u")->where(function (Criteria $criteria) {
            $criteria->on("u.first_name", ':first_name');
            $criteria->on("u.last_name", ':last_name');
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`first_name` = :first_name AND `u`.`last_name` = :last_name";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithOrWhere(): void
    {
        $query = Query::select()->from("users u")->where(function (Criteria $criteria) {
            $criteria->on("u.first_name", ':first_name');
            $criteria->orOn("u.last_name", ':last_name');
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`first_name` = :first_name OR `u`.`last_name` = :last_name";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithOperator(): void
    {
        $query = Query::select()
        ->from("users u")
        ->where(function (Criteria $criteria) {
            $criteria->where("u.id", Operator::greaterOrEqual(1));
            $criteria->where("u.id", Operator::lessOrEqual(100));
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`id` >= 1 AND `u`.`id` <= 100";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithArrayValue(): void
    {
        $query = Query::select()
        ->from("users u")
        ->where(function (Criteria $criteria) {
            $criteria->where("u.id", [1,2,3]);
            $criteria->orWhere("u.id", [4,5,6]);
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`id` IN (1, 2, 3) OR `u`.`id` IN (4, 5, 6)";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithStringArrayValue(): void
    {
        $query = Query::select()
        ->from("users u")
        ->where(function (Criteria $criteria) {
            $criteria->where("u.id", ['a', 'b']);
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`id` IN ('a', 'b')";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithOperatorLike(): void
    {
        $query = Query::select()
        ->from("users u")
        ->where(function (Criteria $criteria) {
            $criteria->where("u.first_name", Operator::like('name'));
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`first_name` LIKE 'name'";
        $this->assertQuery($expected, $query);
    }


    public function testSelectCount(): void
    {
        $query = Query::select()
            ->from("users u")
            ->count()
            ->where(function (Criteria $criteria) {
                $criteria->where("u.id", Operator::greater(100));
            });

        $expected = "SELECT COUNT(*) FROM `users` `u` WHERE `u`.`id` > 100";
        $this->assertQuery($expected, $query);
    }

    public function testSelectExists(): void
    {
        $query = Query::select()->from("users")->exists();

        $expected = "EXISTS(SELECT * FROM `users` )";
        $this->assertQuery($expected, $query);
    }

    public function testSelectSubQuery(): void
    {
        $query = Query::select()->from("users")->columns([
            'users.id id',
            'commentsCount' => Query::select()->from('comments')->count()->where(function ($c) {
                $c->on("c.user_id", Field::equal("users.id"));
            })
        ]);

        $expected = "SELECT `users`.`id` `id`, (SELECT COUNT(*) FROM `comments` WHERE `c`.`user_id` = users.id ) AS `commentsCount` FROM `users`";
        $this->assertQuery($expected, $query);
    }
}
