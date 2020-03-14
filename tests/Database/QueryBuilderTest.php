<?php
namespace Atom\Tests\Database;

use PHPUnit\Framework\TestCase;
use Atom\Database\Query\Compilers\MySqlCompiler;
use Atom\Database\Query\Query;

final class QueryBuilderTest extends TestCase
{
    public function testColumn(): void
    {
        $compiler = new MySqlCompiler();

        $query = Query::select()
                ->from("Users u")
                ->columns(['u.id x', 'firstName', 'lastName'])
                ;

        $sql = $compiler->compileQuery($query);

        var_dump($sql);

        $this->assertEquals("", $sql);
    }
}
