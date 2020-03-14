<?php

namespace Atom\Database\Query\Compilers;

use Atom\Database\Query\Ast\BinaryExpression;
use Atom\Database\Query\Query;

abstract class AbstractCompiler
{
    abstract public function quoteTableName(string $name): string;
    abstract public function quoteColumnName(string $name): string;
    abstract public function quoteValue($value): string;

    private $textWriter;

    public function __construct()
    {
        $this->textWriter = new TextWriter();
    }

    public function compileQuery(Query $query)
    {
        
    }

    public function compileCriteria($criteria)
    {
        if ($criteria instanceof BinaryExpression) {
            //$criteria->leftNode;
            //$criteria->rightNode;
            //$criteria->operator
        }
    }
}


