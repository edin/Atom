<?php

declare(strict_types=1);

namespace Atom\Database\Schema;

final class SchemaPlan
{
    /**
     * @param SchemaCommandBatch[] $batches
     */
    public function __construct(private array $batches = [])
    {
    }

    public function add(SchemaCommandBatch $batch): self
    {
        if (!$batch->isEmpty()) {
            $this->batches[] = $batch;
        }

        return $this;
    }

    /**
     * @return SchemaCommandBatch[]
     */
    public function batches(): array
    {
        return $this->batches;
    }

    /**
     * @return \Atom\Database\Sql\Command[]
     */
    public function commands(): array
    {
        $commands = [];

        foreach ($this->batches as $batch) {
            array_push($commands, ...$batch->commands());
        }

        return $commands;
    }

    /**
     * @return string[]
     */
    public function sql(): array
    {
        return array_map(
            static fn(\Atom\Database\Sql\Command $command): string => $command->sql,
            $this->commands()
        );
    }
}

