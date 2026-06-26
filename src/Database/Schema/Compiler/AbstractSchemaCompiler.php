<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Compiler;

use Atom\Database\Schema\ColumnDefinition;
use Atom\Database\Schema\ColumnType;
use Atom\Database\Schema\IndexDefinition;
use Atom\Database\Schema\Operation\AddTableOperation;
use Atom\Database\Schema\Operation\AlterTableOperation;
use Atom\Database\Schema\Operation\DropTableOperation;
use Atom\Database\Schema\Operation\SchemaOperation;
use Atom\Database\Schema\Schema;
use Atom\Database\Schema\SchemaCommandBatch;
use Atom\Database\Schema\SchemaPlan;
use Atom\Database\Schema\Table;
use Atom\Database\Sql\Command;

abstract class AbstractSchemaCompiler implements SchemaCompilerInterface
{
    public function compile(Schema $schema): SchemaPlan
    {
        $plan = new SchemaPlan();

        foreach ($schema->batches() as $batch) {
            $commands = new SchemaCommandBatch();
            $indexes = new SchemaCommandBatch();

            foreach ($batch->operations() as $operation) {
                foreach ($this->compileOperation($operation) as $command) {
                    $commands->add($command);
                }

                foreach ($this->compileIndexes($operation) as $command) {
                    $indexes->add($command);
                }
            }

            $plan->add($commands);
            $plan->add($indexes);
        }

        return $plan;
    }

    /**
     * @return Command[]
     */
    protected function compileOperation(SchemaOperation $operation): array
    {
        return match (true) {
            $operation instanceof AddTableOperation => [$this->compileCreateTable($operation->table)],
            $operation instanceof AlterTableOperation => $this->compileAlterTable($operation->table),
            $operation instanceof DropTableOperation => [$this->command("DROP TABLE " . $this->name($operation->table))],
            default => [],
        };
    }

    protected function compileCreateTable(Table $table): Command
    {
        $columns = array_map(
            fn(ColumnDefinition $column): string => $this->compileColumn($column, true),
            $table->columns()
        );

        return $this->command("CREATE TABLE " . $this->name($table->name) . " (" . implode(", ", $columns) . ")");
    }

    /**
     * @return Command[]
     */
    protected function compileAlterTable(Table $table): array
    {
        $commands = [];

        foreach ($table->columns() as $column) {
            $commands[] = $this->command(
                "ALTER TABLE " . $this->name($table->name) . " ADD COLUMN " . $this->compileColumn($column, false)
            );
        }

        foreach ($table->droppedColumns() as $column) {
            $commands[] = $this->command(
                "ALTER TABLE " . $this->name($table->name) . " DROP COLUMN " . $this->name($column)
            );
        }

        return $commands;
    }

    protected function compileColumn(ColumnDefinition $column, bool $creatingTable): string
    {
        $parts = [
            $this->name($column->name),
            $this->columnType($column),
        ];

        if ($column->primary) {
            $parts[] = "PRIMARY KEY";
        }

        if ($column->autoIncrement) {
            $parts[] = $this->autoIncrement();
        }

        if (!$column->nullable && !$column->primary) {
            $parts[] = "NOT NULL";
        }

        if ($column->default !== null) {
            $parts[] = "DEFAULT " . $this->literal($column->default);
        }

        return implode(" ", array_filter($parts, static fn(string $part): bool => $part !== ""));
    }

    /**
     * @return Command[]
     */
    protected function compileIndexes(SchemaOperation $operation): array
    {
        if (!$operation instanceof AddTableOperation && !$operation instanceof AlterTableOperation) {
            return [];
        }

        $table = $operation->table;
        $indexes = $table->indexes();

        foreach ($table->columns() as $column) {
            if ($column->unique) {
                $indexes[] = IndexDefinition::unique([$column->name]);
            } elseif ($column->indexed) {
                $indexes[] = IndexDefinition::index([$column->name]);
            }
        }

        return array_map(
            fn(IndexDefinition $index): Command => $this->compileIndex($table->name, $index),
            $indexes
        );
    }

    protected function compileIndex(string $table, IndexDefinition $index): Command
    {
        $name = $index->name ?? $this->defaultIndexName($table, $index);
        $unique = $index->unique ? "UNIQUE " : "";
        $columns = implode(", ", array_map(fn(string $column): string => $this->name($column), $index->columns));

        return $this->command("CREATE {$unique}INDEX " . $this->name($name) . " ON " . $this->name($table) . " ({$columns})");
    }

    protected function defaultIndexName(string $table, IndexDefinition $index): string
    {
        return $table . "_" . implode("_", $index->columns) . ($index->unique ? "_unique" : "_index");
    }

    protected function command(string $sql): Command
    {
        return new Command($sql);
    }

    protected function literal(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? "1" : "0",
            is_int($value), is_float($value) => (string) $value,
            default => "'" . str_replace("'", "''", (string) $value) . "'",
        };
    }

    protected function columnType(ColumnDefinition $column): string
    {
        return match ($column->type) {
            ColumnType::String => $this->stringType($column->length ?? 255),
            ColumnType::Text => $this->textType(),
            ColumnType::Integer => $this->integerType(),
            ColumnType::BigInteger => $this->bigIntegerType(),
            ColumnType::Float => $this->floatType(),
            ColumnType::Decimal => $this->decimalType($column->precision ?? 10, $column->scale ?? 2),
            ColumnType::Boolean => $this->booleanType(),
            ColumnType::Date => $this->dateType(),
            ColumnType::DateTime => $this->dateTimeType(),
            ColumnType::Timestamp => $this->timestampType(),
            ColumnType::Json => $this->jsonType(),
            ColumnType::Binary => $this->binaryType(),
            ColumnType::Uuid => $this->uuidType(),
        };
    }

    abstract protected function name(string $name): string;

    abstract protected function autoIncrement(): string;

    abstract protected function stringType(int $length): string;

    abstract protected function textType(): string;

    abstract protected function integerType(): string;

    abstract protected function bigIntegerType(): string;

    abstract protected function floatType(): string;

    abstract protected function decimalType(int $precision, int $scale): string;

    abstract protected function booleanType(): string;

    abstract protected function dateType(): string;

    abstract protected function dateTimeType(): string;

    abstract protected function timestampType(): string;

    abstract protected function jsonType(): string;

    abstract protected function binaryType(): string;

    abstract protected function uuidType(): string;
}
