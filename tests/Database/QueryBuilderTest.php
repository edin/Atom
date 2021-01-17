<?php

namespace Atom\Tests\Database;

use Atom\Database\Query\Command;
use Atom\Database\Query\Query;
use PHPUnit\Framework\TestCase;
use Atom\Database\Query\Criteria;
use Atom\Database\Query\Operator;
use Atom\Database\Query\Compilers\MySqlCompiler;

final class QueryBuilderTest extends TestCase
{
    private function clean($text)
    {
        if ($text instanceof Command) {
            $text = $text->getSql();
        }

        $text = str_replace('\n', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = str_replace('( ', '(', $text);
        $text = str_replace(' )', ')', $text);
        return trim($text);
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

    public function testSimpleSelectWithLimit(): void
    {
        $query = Query::select()->from("users u")->skip(5)->limit(10);
        $this->assertQuery("SELECT * FROM `users` `u` LIMIT 5, 10", $query);
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
            $criteria->where("u.first_name = :first_name");
            $criteria->where("u.last_name = :last_name");
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`first_name` = :first_name AND `u`.`last_name` = :last_name";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithOrWhere(): void
    {
        $query = Query::select()->from("users u")->where(function (Criteria $criteria) {
            $criteria->where("u.first_name = :first_name");
            $criteria->orWhere("u.last_name = :last_name");
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
                $criteria->where("u.id", [1, 2, 3]);
                $criteria->orWhere("u.id", [4, 5, 6]);
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
                $c->where("comments.user_id = users.id");
            })
        ]);

        $expected = "SELECT `users`.`id` `id`, (SELECT COUNT(*) FROM `comments` WHERE `comments`.`user_id` = `users`.`id` ) AS `commentsCount` FROM `users`";
        $this->assertQuery($expected, $query);
    }


    public function testInsert(): void
    {
        $query = Query::insert()->into("users")->values([
            'id' => 1,
            'first_name' => 'Edin',
            'last_name' => 'Omeragic',
        ]);

        $expected = "INSERT INTO `users` (`id`, `first_name`, `last_name`) VALUES (:id, :first_name, :last_name)";
        $this->assertQuery($expected, $query);
    }

    public function testDelete(): void
    {
        $query = Query::delete()->from("users")->where(function (Criteria $c) {
            $c->where("id = :id", 1);
        })->limit(1);

        $expected = "DELETE FROM `users` WHERE `id` = :id LIMIT 1";
        $this->assertQuery($expected, $query);
    }

    public function testUpdate(): void
    {
        $query = Query::update()->table("users")->values([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ])->where(function (Criteria $c) {
            $c->where("id = :id", 1);
        });

        $expected = "UPDATE `users` SET `first_name` = :first_name, `last_name` = :last_name WHERE `id` = :id";
        $this->assertQuery($expected, $query);
    }
}
