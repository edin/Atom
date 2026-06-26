<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Sql\SelectQuery;

final class DbSelect
{
    /**
     * @param class-string|null $entityClass
     */
    public function __construct(
        private readonly Db $db,
        private readonly SelectQuery $query,
        private readonly ?string $entityClass = null
    ) {
    }

    /** @var string[] */
    private array $relations = [];

    public function query(): SelectQuery
    {
        return $this->query;
    }

    public function columns(string ...$columns): self
    {
        $this->query->columns(...$columns);
        return $this;
    }

    public function count(string $column = "*", ?string $alias = null): self
    {
        $this->query->count($column, $alias);
        return $this;
    }

    public function where(string $column, mixed $value): self
    {
        $this->query->where($column, $value);
        return $this;
    }

    public function orWhere(string $column, mixed $value): self
    {
        $this->query->orWhere($column, $value);
        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function whereExp(string $expression, array $parameters = []): self
    {
        $this->query->whereExp($expression, $parameters);
        return $this;
    }

    public function group(callable $builder): self
    {
        $this->query->group($builder);
        return $this;
    }

    public function join(string $table, ?callable $builder = null): self
    {
        $this->query->join($table, $builder);
        return $this;
    }

    public function leftJoin(string $table, ?callable $builder = null): self
    {
        $this->query->leftJoin($table, $builder);
        return $this;
    }

    public function rightJoin(string $table, ?callable $builder = null): self
    {
        $this->query->rightJoin($table, $builder);
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        $this->query->groupBy(...$columns);
        return $this;
    }

    public function having(string $column, mixed $value): self
    {
        $this->query->having($column, $value);
        return $this;
    }

    public function orderBy(string $column): self
    {
        $this->query->orderBy($column);
        return $this;
    }

    public function orderByDesc(string $column): self
    {
        $this->query->orderByDesc($column);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->query->offset($offset);
        return $this;
    }

    public function with(string ...$relations): self
    {
        array_push($this->relations, ...$relations);
        return $this;
    }

    /**
     * @return array<int, object|array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->entityClass !== null) {
            return $this->loadRelations($this->db->allAs($this->entityClass, $this->query));
        }

        return $this->db->all($this->query);
    }

    /**
     * @return object|array<string, mixed>|null
     */
    public function first(): object|array|null
    {
        if ($this->entityClass !== null) {
            $entity = $this->db->firstAs($this->entityClass, $this->query);
            return is_object($entity) ? $this->loadRelation($entity) : null;
        }

        return $this->db->first($this->query);
    }

    public function scalar(): mixed
    {
        return $this->db->scalar($this->query);
    }

    public function total(string $column = "*"): int
    {
        $query = clone $this->query;
        $query->clearColumns()->count($column);

        return (int) $this->db->scalar($query);
    }

    /**
     * @param object[] $entities
     * @return object[]
     */
    private function loadRelations(array $entities): array
    {
        if ($this->relations !== []) {
            $this->db->loadMany($entities, ...$this->relations);
        }

        return $entities;
    }

    private function loadRelation(object $entity): object
    {
        if ($this->relations !== []) {
            $this->db->load($entity, ...$this->relations);
        }

        return $entity;
    }
}
