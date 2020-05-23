<?php

declare(strict_types=1);

namespace Atom\Database\Query;

use Atom\Application;
use Atom\Database\Database;
use Atom\Database\Interfaces\IConnection;

abstract class Query
{
    protected $from = null;
    protected $table = null;
    protected $count = null;
    protected $isDistinct = null;
    protected $isExists = null;
    protected $limit = null;
    protected $offset = null;
    protected array $columns = [];
    protected array $unions = [];
    protected array $joins = [];
    protected $where = null;
    protected $having = null;
    protected array $orderBy = [];
    protected array $groupBy = [];
    protected $values = null;
    protected ?IConnection $connection = null;

    public function getTable()
    {
        return $this->table;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getIsDistinct()
    {
        return $this->isDistinct;
    }

    public function getIsExists()
    {
        return $this->isExists;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getUnions(): array
    {
        return $this->unions;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getWhere(): ?Criteria
    {
        return $this->where;
    }

    public function getHaving(): ?Criteria
    {
        return $this->having;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getValues()
    {
        return $this->values;
    }

    public static function select(?string $table = null): SelectQuery
    {
        $query = new SelectQuery();
        if ($table !== null) {
            $query->from($table);
        }
        return $query;
    }

    public static function delete(): DeleteQuery
    {
        return new DeleteQuery();
    }

    public static function update(): UpdateQuery
    {
        return new UpdateQuery();
    }

    public static function insert(): InsertQuery
    {
        return new InsertQuery();
    }

    public function setConnection(IConnection $connection): void
    {
        $this->connection = $connection;
    }

    public function getConnection(): IConnection
    {
        if ($this->connection === null) {
            return Application::$app->getContainer()->get(Database::class)->getConnection($this);
        }
        return $this->connection;
    }

    public function compileQuery(): Command
    {
        return $this->getConnection()->compileQuery($this);
    }
}
