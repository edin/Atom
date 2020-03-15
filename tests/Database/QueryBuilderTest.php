<?php
namespace Atom\Tests\Database;

use PHPUnit\Framework\TestCase;
use Atom\Database\Query\Compilers\MySqlCompiler;
use Atom\Database\Query\Criteria;
use Atom\Database\Query\Query;

final class QueryBuilderTest extends TestCase
{
    public function testColumn(): void
    {
        $compiler = new MySqlCompiler();

        $query = Query::select()
                ->from("Users u")
                ->columns(['u.id x', 'firstName', 'lastName'])
                ->orderBy("u.id")
                ->orderByDesc("firstName")
                ->groupBy("u.id")
                ->join("Comments c", function(Criteria $criteria) {

                })
                ->where(function(Criteria $criteria) {
                    
                })
                ;

        $sql = $compiler->compileQuery($query);

        var_dump($sql);

        $this->assertEquals("", $sql);
    }
}
