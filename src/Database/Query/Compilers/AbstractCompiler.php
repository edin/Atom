<?php

namespace Atom\Database\Query\Compilers;

use Closure;
use Atom\Database\Query\Query;
use Atom\Database\Query\Ast\Join;
use Atom\Database\Query\Criteria;
use Atom\Database\Query\Operator;
use Atom\Database\Query\Ast\Table;
use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\DeleteQuery;
use Atom\Database\Query\InsertQuery;
use Atom\Database\Query\SelectQuery;
use Atom\Database\Query\UpdateQuery;
use Atom\Database\Query\Ast\SortOrder;
use Atom\Database\Query\Ast\GroupExpression;
use Atom\Database\Query\Ast\UnaryExpression;
use Atom\Database\Query\Ast\BinaryExpression;

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

    private function emit(string $text): void
    {
        $this->textWriter->write($text);
    }

    private function indent(): void
    {
        $this->textWriter->indent();
    }

    private function unindent(): void
    {
        $this->textWriter->unindent();
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
        } elseif ($node instanceof DeleteQuery) {
            $this->visitDeleteQuery($node);
        } elseif ($node instanceof InsertQuery) {
            $this->visitInsertQuery($node);
        } elseif ($node instanceof UpdateQuery) {
            $this->visitUpdateQuery($node);
        } elseif ($node instanceof Column) {
            $this->visitColumn($node);
        } elseif ($node instanceof GroupExpression) {
            $this->visitGroupExpression($node);
        } elseif ($node instanceof BinaryExpression) {
            $this->visitBinaryExpression($node);
        } elseif ($node instanceof UnaryExpression) {
            $this->visitUnaryExpression($node);
        } elseif ($node instanceof Operator) {
            $this->visitOperator($node);
        } elseif ($node instanceof Criteria) {
            $this->visitCriteria($node);
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
            $this->emit("SELECT COUNT($count) ");
        } elseif (count($columns) > 0) {
            $this->emit("SELECT ");
            $this->visitColumns($columns);
        } else {
            $this->emit("SELECT * ");
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
            $this->viistUnions($unions);
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

    private function visitList(array $nodes, Closure $callback)
    {
        $index = 0;
        $total = count($nodes);

        $this->indent();
        foreach ($nodes as $node) {
            $index++;
            $callback($node);
            if ($index < $total) {
                $this->emit(",\n");
            }
        }
        $this->unindent();
        $this->emit("\n");
    }

    protected function visitColumns(array $nodes): void
    {
        $this->indent();
        $this->emit("\n");
        $this->unindent();
        $this->visitList($nodes, function ($node) {
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
        $this->emit("ORDER BY ");
        $this->visitList($nodes, function ($node) {
            $this->visitOrder($node);
        });
    }

    protected function visitGroupBys(array $nodes): void
    {
        $this->emit("GROUP BY ");
        $this->visitList($nodes, function ($node) {
            $this->visitColumn($node);
        });
    }

    protected function visitUnions(array $nodes): void
    {
        foreach ($nodes as $node) {
            $this->emit("UNION ");
            $this->visitSelectQuery($node);
        }
    }

    protected function visitHaving(Criteria $node): void
    {
        $this->emit("HAVING ");
        $this->visitCriteria($node);
    }

    protected function visitColumn(Column $node): void
    {
        if ($node->expression) {
            $this->visitNode($node->expression);
            $alias = $this->quoteColumnName($node->alias);
            $this->emit(" AS {$alias}");
        } else {
            $columnName = $this->quoteColumnName($node->name);
            if ($node->table) {
                $columnName = $this->quoteTableName($node->table) . "." . $columnName;
            }
            if ($node->alias) {
                $alias = $this->quoteColumnName($node->alias);
                $columnName .= " {$alias}";
            }
            $this->emit($columnName);
        }
    }

    protected function visitFrom(Table $node): void
    {
        $this->emit("FROM ");
        $this->visitTable($node);
        $this->emit("\n");
    }

    protected function visitTable(Table $node): void
    {
        $tableName =  $this->quoteTableName($node->name);
        if ($node->alias) {
            $alias = $this->quoteColumnName($node->alias);
            $tableName .= " {$alias}";
        }
        $this->emit($tableName);
    }

    protected function visitJoin(Join $node): void
    {
        $joinType = [
            Join::Join => "JOIN ",
            Join::LeftJoin => "LEFT JOIN ",
            Join::RightJoin => "RIGHT JOIN "
        ];

        $join = $joinType[$node->joinType];

        $this->emit($join);
        $this->emit(" ");
        $this->visitTable($node->table);
        $this->emit(" ON ");
        $this->visitCriteria($node->joinCondition);
        $this->emit("\n");
    }

    protected function visitOrder(SortOrder $node): void
    {
        $this->visitNode($node->expression);
        $order = $node->order;
        if ($node->nullsOrder) {
            $order .= " {$node->nullsOrder}";
        }
        $this->emit(" {$order}");
    }

    protected function visitCriteria(Criteria $node): void
    {
        if ($node->hasExpression()) {
            $this->visitNode($node->getExpression());
        }
    }

    protected function visitWhere(Criteria $node): void
    {
        if ($node->hasExpression()) {
            $this->emit("WHERE ");
            $this->visitCriteria($node);
            $this->emit("\n");
        }
    }

    protected function visitBinaryExpression(BinaryExpression $node): void
    {
        $this->visitNode($node->leftNode);
        $this->emit(" {$node->operator} ");
        $this->visitNode($node->rightNode);
    }

    protected function visitGroupExpression(GroupExpression $node): void
    {
        $this->emit("(");
        $this->visitNode($node->node);
        $this->emit(")");
        $this->emit("\n");
    }

    protected function visitUnaryExpression(UnaryExpression $node): void
    {
        $this->emit($node->operator);
        $this->visitNode($node->node);
    }

    protected function visitOperator(Operator $node): void
    {
        $this->emit($node->getValue());
    }
}
