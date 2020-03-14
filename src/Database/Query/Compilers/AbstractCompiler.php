<?php

namespace Atom\Database\Query\Compilers;

use Atom\Database\Query\Ast\BinaryExpression;
use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\Ast\GroupExpression;
use Atom\Database\Query\Ast\Join;
use Atom\Database\Query\Ast\SortOrder;
use Atom\Database\Query\Ast\Table;
use Atom\Database\Query\Ast\UnaryExpression;
use Atom\Database\Query\DeleteQuery;
use Atom\Database\Query\InsertQuery;
use Atom\Database\Query\Query;
use Atom\Database\Query\SelectQuery;
use Atom\Database\Query\UpdateQuery;

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
        $this->visitNode($query);
    }

    protected function visitNode($node) {
        if ($node instanceof SelectQuery) {
            $this->visitSelectQuery($node);
        } 
        else if ($node instanceof DeleteQuery) {
            $this->visitDeleteQuery($node);
        }
        else if ($node instanceof InsertQuery) {
            $this->visitInsertQuery($node);
        }
        else if ($node instanceof UpdateQuery) {
            $this->visitUpdateQuery($node);
        }
    }

    protected function visitSelectQuery(SelectQuery $query) {
    }

    protected function visitDeleteQuery(DeleteQuery $query) {
    }

    protected function visitUpdateQuery(UpdateQuery $query) {
    }

    protected function visitInsertQuery(InsertQuery $query) {
    }
    
    protected function visitColumns(array $columns) {
        foreach($columns as $column) {
            $this->visitJoin($column);
        }
    }

    protected function visitJoins(array $joins) {
        foreach($joins as $join) {
            $this->visitJoin($join);
        }
    }

    protected function visitOrderBys(array $orderBys) {
        foreach($orderBys as $orderBy) {
            $this->visitOrder($orderBy);
        }
    }

    protected function visitGroupBys(array $groupBys) {
        foreach($groupBys as $groupBy) {
            $this->visitGroupBy($groupBy);
        }        
    }

    protected function visitHaving(array $havings) {
        
    }

    protected function visitColumn(Column $column) {
        
    }

    protected function visitTable(Table $table) {

    }

    protected function visitJoin(Join $sortOrder) {
        
    }

    protected function visitOrder(SortOrder $sortOrder) {
        
    }

    protected function visitGroupBy(Column $groupBy) {
        
    }

    protected function visitBinaryExpression(BinaryExpression $node) {
        
    }

    protected function visitGroupExpression(GroupExpression $node) {
        
    }

    protected function visitUnaryExpression(UnaryExpression $node) {
        
    }

    // public function compileCriteria($criteria)
    // {
    //     if ($criteria instanceof BinaryExpression) {
    //         //$criteria->leftNode;
    //         //$criteria->rightNode;
    //         //$criteria->operator
    //     }
    // }
}