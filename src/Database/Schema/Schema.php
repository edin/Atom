<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

use Atom\Database\Schema\Inspector\NullSchemaInspector;
use Atom\Database\Schema\Inspector\SchemaInspectorInterface;
use Atom\Database\Schema\Operation\AddTableOperation;
use Atom\Database\Schema\Operation\AlterTableOperation;
use Atom\Database\Schema\Operation\DropTableOperation;
use Atom\Database\Schema\Operation\SchemaOperationInterface;

final class Schema
{
    /** @var SchemaBatch[] */
    private array $batches = [];

    public function __construct(private readonly SchemaInspectorInterface $inspector = new NullSchemaInspector())
    {
    }

    public function hasTable(string $table): bool
    {
        return $this->inspector->hasTable($table);
    }

    public function hasColumn(string $table, string $column): bool
    {
        return $this->inspector->hasColumn($table, $column);
    }

    /**
     * @return string[]
     */
    public function columns(string $table): array
    {
        return $this->inspector->columns($table);
    }

    public function create(string $name, callable $definition): self
    {
        $table = new Table($name);
        $definition($table);

        return $this->addOperation(new AddTableOperation($table));
    }

    public function table(string $name, callable $definition): self
    {
        $table = new Table($name);
        $definition($table);

        return $this->addOperation(new AlterTableOperation($table));
    }

    public function drop(string $name): self
    {
        return $this->addOperation(new DropTableOperation($name));
    }

    public function batch(callable $definition): self
    {
        $batch = new SchemaBatch();
        $collector = new self($this->inspector);
        $definition($collector);

        foreach ($collector->operations() as $operation) {
            $batch->add($operation);
        }

        if (!$batch->isEmpty()) {
            $this->batches[] = $batch;
        }

        return $this;
    }

    /**
     * @return SchemaBatch[]
     */
    public function batches(): array
    {
        return $this->batches;
    }

    /**
     * @return SchemaOperationInterface[]
     */
    public function operations(): array
    {
        $operations = [];

        foreach ($this->batches as $batch) {
            array_push($operations, ...$batch->operations());
        }

        return $operations;
    }

    private function addOperation(SchemaOperationInterface $operation): self
    {
        $this->batches[] = new SchemaBatch([$operation]);
        return $this;
    }
}
