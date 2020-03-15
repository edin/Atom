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
use Closure;

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
        } else if ($node instanceof Column) {
            $this->visitColumn($node);
        } else {
            $name = get_class($node);
            throw new \RuntimeException("Missing overload for type $name");
        }
    }

    protected function visitSelectQuery(SelectQuery $query)
    {
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

    private function visitList(array $nodes, Closure $callback) {
        $index = 0;
        $total = count($nodes);

        $this->textWriter->ident();
        foreach ($nodes as $node) {
            $index ++;
            $callback($node);
            if ($index < $total) {
                $this->textWriter->write(", ");
            }
        }
        $this->textWriter->unident();
        $this->textWriter->write("\n");        
    }

    protected function visitColumns(array $nodes): void
    {
        $this->visitList($nodes,  function($node) {
            $this->visitColumn($node);
        });        
    }

    protected function visitJoins(array $joins): void
    {
        foreach ($joins as $join) {
            $this->visitJoin($join);
        }
    }

    protected function visitOrderBys(array $nodes): void
    {
        $this->textWriter->write("ORDER BY ");
        $this->visitList($nodes,  function($node) {
            $this->visitOrder($node);
        });
    }

    protected function visitGroupBys(array $nodes): void
    {
        $this->textWriter->write("GROUP BY ");
        $this->visitList($nodes, function($node) {
            $this->visitColumn($node);
        });
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
                $columnName .= " {$node->alias}";
            }
            $this->textWriter->write($columnName);
        }
    }

    protected function visitFrom(Table $node): void
    {
        $tableName = $node->name;
        if ($node->alias) {
            $tableName .= " {$node->alias}";
        }
        $this->textWriter->write("FROM $tableName\n");
    }

    // protected function visitFromSelect(SelectQuery $node): void
    // {
    //     $this->textWriter->write("(");
    //     $this->visitSelectQuery($node);
    //     $this->textWriter->write(")");
    // }

    protected function visitJoin(Join $node): void
    {
        $node->joinType

        throw new \RuntimeException("Method visitJoin not implemented");
    }

    protected function visitOrder(SortOrder $node): void
    {
        $this->visitNode($node->expression);

        $order = $node->order;
        if ($node->nullsOrder) {
            $order .= " {$node->nullsOrder}";
        }

        $this->textWriter->write(" " . $order);
    }

    protected function visitCriteria(Criteria $node): void
    {
        throw new \RuntimeException("Method visitCriteria not implemented");
    }

    protected function visitWhere(Criteria $node): void
    {
        throw new \RuntimeException("Method visitWhere not implemented");
    }

    protected function visitBinaryExpression(BinaryExpression $node): void
    {
        throw new \RuntimeException("Method visitBinaryExpression not implemented");
    }

    protected function visitGroupExpression(GroupExpression $node): void
    {
        throw new \RuntimeException("Method visitGroupExpression not implemented");
    }

    protected function visitUnaryExpression(UnaryExpression $node): void
    {
        throw new \RuntimeException("Method visitUnaryExpression not implemented");
    }
}
