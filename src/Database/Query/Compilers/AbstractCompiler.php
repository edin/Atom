<?php

namespace Atom\Database\Query\Compilers;

use Atom\Database\Query\Ast\BinaryExpression;
use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\Ast\GroupExpression;
use Atom\Database\Query\Ast\Join;
use Atom\Database\Query\Ast\SortOrder;
use Atom\Database\Query\Ast\Table;
use Atom\Database\Query\Ast\UnaryExpression;
use Atom\Database\Query\Criteria;
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
        return $this->textWriter->getText();
    }

    protected function visitNode($node)
    {
        if ($node instanceof SelectQuery) {
            $this->visitSelectQuery($node);
        } else if ($node instanceof DeleteQuery) {
            $this->visitDeleteQuery($node);
        } else if ($node instanceof InsertQuery) {
            $this->visitInsertQuery($node);
        } else if ($node instanceof UpdateQuery) {
            $this->visitUpdateQuery($node);
        }
    }

    protected function visitSelectQuery(SelectQuery $query)
    {
        // var_dump($query);

        $count = $query->getCount();
        $columns = $query->getColumns();
        $table = $query->getTable();
        $joins = $query->getJoins();
        $where = $query->getWhere();
        $groupBys = $query->getGroupBy();
        $having = $query->getHaving();
        $orderBys = $query->getOrderBy();
        $unions = $query->getUnions();

        if ($count !== null) {
            $this->textWriter->write("SELECT COUNT($count)");
        } else {
            $this->textWriter->write("SELECT");
            $this->visitColumns($columns);
        }

        if ($table) {
            // TODO: Add support to visit from SelectQuery
            $this->visitFrom($table);
        }
        if ($joins) {
            $this->visitJoins($joins);
        }
        if ($where) {
            $this->visitWhere($where);
        }
        if ($groupBys) {
            $this->visitGroupBys($groupBys);
        }
        if ($having) {
            $this->visitHaving($having);
        }
        if ($orderBys) {
            $this->visitOrderBys($orderBys);
        }
        if ($unions) {
            $this->visitOrderBys($unions);
        }
    }

    protected function visitDeleteQuery(DeleteQuery $query): void
    {
    }

    protected function visitUpdateQuery(UpdateQuery $query): void
    {
    }

    protected function visitInsertQuery(InsertQuery $query): void
    {
    }

    protected function visitColumns(array $columns): void
    {
        $index = 0;
        $total = count($columns);
        $this->textWriter->ident();
        $this->textWriter->write("\n");
        foreach ($columns as $column) {
            $index ++;
            $this->visitColumn($column);
            if ($index < $total) {
                $this->textWriter->write(",\n");
            }
        }
        $this->textWriter->unident();
        $this->textWriter->write("\n");
    }

    protected function visitJoins(array $joins): void
    {
        foreach ($joins as $join) {
            $this->visitJoin($join);
        }
    }

    protected function visitOrderBys(array $orderBys): void
    {
        foreach ($orderBys as $orderBy) {
            $this->visitOrder($orderBy);
        }
    }

    protected function visitGroupBys(array $groupBys): void
    {
        foreach ($groupBys as $groupBy) {
            $this->visitGroupBy($groupBy);
        }
    }

    protected function visitHaving(Criteria $node): void
    {
    }

    protected function visitColumn(Column $node): void
    {
        if ($node->expression) {
            $this->visitNode($node->expression);
            $this->textWriter->write(" AS {$node->alias}");
        } else {
            $columnName = $node->name;
            if ($node->table) {
                $columnName = $node->table . "." . $columnName;
            }
            if ($node->alias) {
                $columnName .= " AS {$node->alias}";
            }
            $this->textWriter->write($columnName);
        }
    }

    protected function visitFrom(Table $node): void
    {
        $tableName = $node->name;
        if ($node->alias) {
            $tableName .= " AS {$node->alias}";
        }

        $this->textWriter->write("FROM $tableName\n");
    }

    protected function visitFromSelect(Table $node): void
    {
        throw new \RuntimeException("Method not implemented");
    }


    protected function visitJoin(Join $node): void
    {
    }

    protected function visitOrder(SortOrder $node): void
    {
    }

    protected function visitGroupBy(Column $node): void
    {
    }

    protected function visitCriteria(Criteria $node): void
    {
    }

    protected function visitWhere(Criteria $node): void
    {
    }

    protected function visitBinaryExpression(BinaryExpression $node): void
    {
    }

    protected function visitGroupExpression(GroupExpression $node): void
    {
    }

    protected function visitUnaryExpression(UnaryExpression $node): void
    {
    }
}
