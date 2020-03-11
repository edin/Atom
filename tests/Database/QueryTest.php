<?php
namespace Atom\Tests\Database;

use PHPUnit\Framework\TestCase;
use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\Ast\Table;

final class QueryTest extends TestCase
{
    public function testColumn(): void
    {
        $column1 = Column::fromValue("field");
        $column2 = Column::fromValue("table.field");
        $column3 = Column::fromValue("field alias");
        $column4 = Column::fromValue("table.field alias");

        $this->assertEquals($column1->table, null);
        $this->assertEquals($column1->name, "field");
        $this->assertEquals($column1->alias, null);

        $this->assertEquals($column2->table, "table");
        $this->assertEquals($column2->name, "field");
        $this->assertEquals($column2->alias, null);

        $this->assertEquals($column3->table, null);
        $this->assertEquals($column3->name, "field");
        $this->assertEquals($column3->alias, "alias");


        $this->assertEquals($column4->table, "table");
        $this->assertEquals($column4->name, "field");
        $this->assertEquals($column4->alias, "alias");
    }

    public function testTable(): void
    {
        $table1 = Table::fromValue("table");
        $table2 = Table::fromValue("table t");

        $this->assertEquals($table1->name, "table");
        $this->assertEquals($table1->alias, null);

        $this->assertEquals($table2->name, "table");
        $this->assertEquals($table2->alias, "t");
    }
}
