<?php

namespace Atom\Database\Query\Compilers;

use Closure;
use Atom\Database\Query\Field;
use Atom\Database\Query\Query;
use Atom\Database\Query\Ast\Join;
use Atom\Database\Query\Criteria;
use Atom\Database\Query\Operator;
use Atom\Database\Command\Command;
use Atom\Database\Query\Ast\Table;
use Atom\Database\Query\Parameter;
use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\DeleteQuery;
use Atom\Database\Query\InsertQuery;
use Atom\Database\Query\SelectQuery;
use Atom\Database\Query\UpdateQuery;
use Atom\Database\Query\Ast\SortOrder;
use Atom\Database\Interfaces\IQueryCompiler;
use Atom\Database\Query\Ast\GroupExpression;
use Atom\Database\Query\Ast\UnaryExpression;
use Atom\Database\Query\Ast\BinaryExpression;

abstract class AbstractCompiler implements IQueryCompiler
{
    private $textWriter;

    abstract public function quoteTableName(string $name): string;
    abstract public function quoteColumnName(string $name): string;

    public function quoteValue($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_int($value)) {
            return (string)$value;
        }

        if (is_float($value)) {
            return (string)$value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            $items = [];
            foreach ($value as $it) {
                $items[] = $this->quoteValue($it);
            }
            return "(" . implode(", ", $items) . ")";
        }

        $value = (string)$value;
        $value = str_replace("'", "''", $value);
        return "'$value'";
    }

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

    public function compileQuery(Query $query): Command
    {
        $this->visitNode($query);
        $command = new Command;
        $sql =  $this->textWriter->getText();
        $command->setSql($sql);
        return $command;
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
        } elseif ($node instanceof Field) {
            $this->visitField($node);
        } elseif ($node instanceof Parameter) {
            $this->visitParameter($node);
        } elseif ($node instanceof Table) {
            $this->visitTable($node);
        } else {
            $name = is_object($node) ? get_class($node) : gettype($node);
            throw new \RuntimeException("Missing overload for type $name");
        }
    }

    protected function visitSelectQuery(SelectQuery $query)
    {
        $count = $query->getCount();
        $columns = $query->getColumns();
        $isExists =  $query->getIsExists();
        $isDistinct =  $query->getIsDistinct();

        $distinct = ($isDistinct) ? "DISTINCT ": "";

        if ($isExists !== null) {
            if ($isExists=== true) {
                $this->emit("EXISTS(");
            } else {
                $this->emit("NOT EXISTS(");
            }
        }

        if ($count !== null) {
            $this->emit("SELECT COUNT({$distinct}$count) ");
        } elseif (count($columns) > 0) {
            $this->emit("SELECT $distinct");
            $this->visitColumns($columns);
        } else {
            $this->emit("SELECT $distinct* ");
        }

        $this->visitFrom($query->getFrom());
        $this->visitJoins($query->getJoins());
        $this->visitWhere($query->getWhere());
        $this->visitGroupBys($query->getGroupBy());
        $this->visitHaving($query->getHaving());
        $this->visitOrderBys($query->getOrderBy());
        $this->visitUnions($query->getUnions());

        $this->emitLimits($query);

        if ($isExists !== null) {
            $this->emit(")");
        }
    }

    protected function emitLimits(Query $query)
    {
        $offset = $query->getOffset();
        $limit = $query->getLimit();
        if ($offset && $limit) {
            $this->emit("LIMIT $offset, $limit");
        } elseif ($limit) {
            $this->emit("LIMIT $limit");
        }
    }

    protected function visitDeleteQuery(DeleteQuery $query): void
    {
        $this->emit("DELETE ");
        $this->visitFrom($query->getTable());
        $this->visitJoins($query->getJoins());
        $this->visitWhere($query->getWhere());
        $this->emitLimits($query);
    }

    protected function visitUpdateQuery(UpdateQuery $query): void
    {
        $values = $query->getValues();

        $this->emit("UPDATE ");
        $this->visitTable($query->getTable());
        $this->emit(" SET ");

        $fields = [];
        foreach ($values as $key => $value) {
            $field = Field::from($key);
            $parameter = is_object($value) ? $value : Parameter::from(":{$field->name}", $value);
            $fields[] = [$field, $parameter];
        }

        $this->visitList($fields, function ($item) {
            $this->visitField($item[0]);
            $this->emit(" = ");
            $this->visitNode($item[1]);
        });

        $this->visitFrom($query->getFrom());
        $this->visitJoins($query->getJoins());
        $this->visitWhere($query->getWhere());
        $this->visitGroupBys($query->getGroupBy());
        $this->visitHaving($query->getHaving());
        $this->visitOrderBys($query->getOrderBy());
        $this->visitUnions($query->getUnions());
        $this->emitLimits($query);
    }

    protected function visitInsertQuery(InsertQuery $query): void
    {
        $values = $query->getValues();

        $this->emit("INSERT INTO ");
        $this->visitNode($query->getTable());
        $this->emit(" (");
        $this->indent();
        $this->emit("\n");

        $fieldNames = [];
        $parameters = [];
        foreach ($values as $key => $value) {
            $fieldNames[$key] = $field = Field::from($key);
            $parameter = is_object($value) ? $value : Parameter::from(":{$field->name}", $value);
            $parameters[$key] = $parameter;
        }

        $this->visitList($fieldNames, function ($field) {
            $this->visitField($field);
        });

        $this->unindent();
        $this->emit("\n) VALUES (");
        $this->indent();

        $this->visitList($parameters, function ($parameter) {
            $this->visitParameter($parameter);
        });

        $this->emit("\n");
        $this->emit(")");

        $this->visitFrom($query->getFrom());
        $this->visitJoins($query->getJoins());
        $this->visitWhere($query->getWhere());
        $this->visitGroupBys($query->getGroupBy());
        $this->visitHaving($query->getHaving());
        $this->visitOrderBys($query->getOrderBy());
        $this->visitUnions($query->getUnions());
        $this->emitLimits($query);
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
        if (count($nodes)) {
            $this->emit("ORDER BY ");
            $this->visitList($nodes, function ($node) {
                $this->visitOrder($node);
            });
        }
    }

    protected function visitGroupBys(array $nodes): void
    {
        if (count($nodes)) {
            $this->emit("GROUP BY ");
            $this->visitList($nodes, function ($node) {
                $this->visitColumn($node);
            });
        }
    }

    protected function visitUnions(array $nodes): void
    {
        if (count($nodes)) {
            foreach ($nodes as $node) {
                $this->emit("UNION ");
                $this->visitSelectQuery($node);
            }
        }
    }

    protected function visitHaving(?Criteria $node): void
    {
        if ($node) {
            $this->emit("HAVING ");
            $this->visitCriteria($node);
        }
    }

    protected function visitColumn(Column $node): void
    {
        if ($node->expression) {
            $this->emit("(");
            $this->visitNode($node->expression);
            $this->emit(")");
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

    protected function visitField(Field $node): void
    {
        $columnName = $this->quoteColumnName($node->name);
        if ($node->table) {
            $columnName = $this->quoteTableName($node->table) . "." . $columnName;
        }
        $this->emit($columnName);
    }

    protected function visitParameter(Parameter $node): void
    {
        $this->emit($node->getName());
        //TODO: Store parameter value for later use
    }


    protected function visitFrom(?Table $node): void
    {
        if ($node) {
            $this->emit("FROM ");
            $this->visitTable($node);
            $this->emit("\n");
        }
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

    protected function visitCriteria(?Criteria $node): void
    {
        if ($node && $node->hasExpression()) {
            $this->visitNode($node->getExpression());
        }
    }

    protected function visitWhere(?Criteria $node): void
    {
        if ($node && $node->hasExpression()) {
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
    }

    protected function visitUnaryExpression(UnaryExpression $node): void
    {
        $this->emit($node->operator);
        $this->visitNode($node->node);
    }

    protected function visitOperator(Operator $node): void
    {
        $expression = $node->getExpression();

        if ($expression instanceof SelectQuery) {
            $this->visitNode($expression);
        } else {
            $this->emit($this->quoteValue($expression));
        }
    }
}
