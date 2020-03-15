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
            $criteria->where("u.first_name", ':first_name');
            $criteria->where("u.last_name", ':last_name');
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`first_name` = :first_name AND `u`.`last_name` = :last_name";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithOrWhere(): void
    {
        $query = Query::select()->from("users u")->where(function (Criteria $criteria) {
            $criteria->where("u.first_name", ':first_name');
            $criteria->orWhere("u.last_name", ':last_name');
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`first_name` = :first_name OR `u`.`last_name` = :last_name";
        $this->assertQuery($expected, $query);
    }

    public function testSimpleSelectWithOperator(): void
    {
        $query = Query::select()->from("users u")->where(function (Criteria $criteria) {
            $criteria->where("u.id", Operator::greaterOrEqual(1));
            $criteria->Where("u.id", Operator::lessOrEqual(100));
        });

        $expected = "SELECT * FROM `users` `u` WHERE `u`.`id` >= 1 AND `u`.`id` <= 100";
        $this->assertQuery($expected, $query);
    }
}
