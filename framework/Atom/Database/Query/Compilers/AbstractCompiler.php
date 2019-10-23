<?php

namespace Atom\Database\Query\Compilers;

use Atom\Database\Query\Ast\BinaryExpression;
use Atom\Database\Query\Ast\Join;

abstract class AbstractCompiler
{
    abstract public function quoteTableName(string $name): string;
    abstract public function quoteColumnName(string $name): string;
    abstract public function quoteValue(string $value): string;

    public function compileCriteria($criteria)
    {
        if ($criteria instanceof BinaryExpression) {
            //$criteria->leftNode;
            //$criteria->rightNode;
            //$criteria->operator
        }
    }
}
