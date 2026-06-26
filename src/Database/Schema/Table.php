<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

final class Table
{
    /** @var ColumnDefinition[] */
    private array $columns = [];
    /** @var IndexDefinition[] */
    private array $indexes = [];
    /** @var string[] */
    private array $droppedColumns = [];

    public function __construct(public readonly string $name)
    {
    }

    public function id(string $name = "id"): ColumnDefinition
    {
        return $this->integer($name)->primary()->autoIncrement();
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->column($name, ColumnType::String, length: $length);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->column($name, ColumnType::Text);
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->column($name, ColumnType::Integer);
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->column($name, ColumnType::Boolean);
    }

    public function dateTime(string $name): ColumnDefinition
    {
        return $this->column($name, ColumnType::DateTime);
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->column($name, ColumnType::Timestamp);
    }

    public function timestamps(): self
    {
        $this->timestamp("created_at")->nullable();
        $this->timestamp("updated_at")->nullable();

        return $this;
    }

    public function dropColumn(string $name): self
    {
        $this->droppedColumns[] = $name;
        return $this;
    }

    public function index(string|array $columns, ?string $name = null): IndexDefinition
    {
        return $this->addIndex(IndexDefinition::index($this->normalizeColumns($columns), $name));
    }

    public function unique(string|array $columns, ?string $name = null): IndexDefinition
    {
        return $this->addIndex(IndexDefinition::unique($this->normalizeColumns($columns), $name));
    }

    public function column(
        string $name,
        ColumnType $type,
        ?int $length = null,
        mixed $default = null
    ): ColumnDefinition {
        $column = new ColumnDefinition($name, $type, $length, $default);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * @return ColumnDefinition[]
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return IndexDefinition[]
     */
    public function indexes(): array
    {
        return $this->indexes;
    }

    /**
     * @return string[]
     */
    public function droppedColumns(): array
    {
        return $this->droppedColumns;
    }

    private function addIndex(IndexDefinition $index): IndexDefinition
    {
        $this->indexes[] = $index;
        return $index;
    }

    /**
     * @return string[]
     */
    private function normalizeColumns(string|array $columns): array
    {
        return is_array($columns) ? array_values($columns) : [$columns];
    }
}

