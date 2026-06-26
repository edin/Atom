<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final class UpdateQuery implements SqlQuery
{
    private ?Table $table = null;
    /** @var array<string, mixed> */
    private array $values = [];
    private WhereGroup $where;

    public function __construct()
    {
        $this->where = new WhereGroup();
    }

    public function table(string $table): self
    {
        $this->table = Table::from($table);
        return $this;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function set(array $values): self
    {
        foreach ($values as $column => $value) {
            $this->setValue($column, $value);
        }

        return $this;
    }

    public function setValue(string $column, mixed $value): self
    {
        $this->values[$column] = $value;
        return $this;
    }

    public function where(string $column, mixed $value): self
    {
        $this->where->where($column, $value);
        return $this;
    }

    public function orWhere(string $column, mixed $value): self
    {
        $this->where->orWhere($column, $value);
        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function whereExp(string $expression, array $parameters = []): self
    {
        $this->where->whereExp($expression, $parameters);
        return $this;
    }

    public function getTable(): ?Table
    {
        return $this->table;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function getWhere(): WhereGroup
    {
        return $this->where;
    }
}
