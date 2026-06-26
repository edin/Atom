<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

use Atom\Database\DatabaseConnection;
use Atom\Database\Schema\Schema;
use Atom\Database\Schema\SchemaPlan;
use RuntimeException;

final readonly class Migrator
{
    public function __construct(
        private DatabaseConnection $connection,
        private MigrationRepositoryInterface $repository,
        private MigrationLockManagerInterface $locks,
        private MigrationDiscovery $discovery,
        private MigrationOptions $options
    ) {
    }

    /**
     * @return MigrationDefinition[]
     */
    public function all(): array
    {
        return $this->discovery->discover($this->options);
    }

    /**
     * @return MigrationDefinition[]
     */
    public function pending(): array
    {
        $applied = array_flip($this->repository->applied());

        return array_values(array_filter(
            $this->all(),
            static fn(MigrationDefinition $migration): bool => !isset($applied[$migration->name])
        ));
    }

    /**
     * @return MigrationStatus[]
     */
    public function status(): array
    {
        $applied = array_flip($this->repository->applied());

        return array_map(
            static fn(MigrationDefinition $migration): MigrationStatus => new MigrationStatus(
                $migration->name,
                isset($applied[$migration->name])
            ),
            $this->all()
        );
    }

    public function run(): MigrationRunResult
    {
        if (!$this->locks->acquire()) {
            throw new RuntimeException("Migrations are already running.");
        }

        try {
            $batch = $this->repository->latestBatch() + 1;
            $migrated = [];

            foreach ($this->pending() as $migration) {
                $plan = $this->plan($migration, "up");
                $this->execute($plan);
                $this->repository->record($migration->name, $batch);
                $migrated[] = $migration->name;
            }

            return new MigrationRunResult($migrated);
        } finally {
            $this->locks->release();
        }
    }

    public function rollback(int $steps = 1): MigrationRollbackResult
    {
        if (!$this->locks->acquire()) {
            throw new RuntimeException("Migrations are already running.");
        }

        try {
            $latestBatch = $this->repository->latestBatch();
            if ($latestBatch === 0) {
                return new MigrationRollbackResult([]);
            }

            $steps = max(1, $steps);
            $definitions = [];
            foreach ($this->all() as $definition) {
                $definitions[$definition->name] = $definition;
            }

            $rolledBack = [];
            for ($batch = $latestBatch; $batch >= 1 && $steps > 0; $batch--, $steps--) {
                foreach ($this->repository->batch($batch) as $migration) {
                    if (!isset($definitions[$migration])) {
                        throw new RuntimeException("Migration '{$migration}' was recorded but its file was not found.");
                    }

                    $plan = $this->plan($definitions[$migration], "down");
                    $this->execute($plan);
                    $this->repository->delete($migration);
                    $rolledBack[] = $migration;
                }
            }

            return new MigrationRollbackResult($rolledBack);
        } finally {
            $this->locks->release();
        }
    }

    /**
     * @return array<string, string[]>
     */
    public function sql(): array
    {
        $sql = [];

        foreach ($this->pending() as $migration) {
            $sql[$migration->name] = $this->plan($migration, "up")->sql();
        }

        return $sql;
    }

    private function plan(MigrationDefinition $definition, string $method): SchemaPlan
    {
        $migration = $definition->create();
        $schema = new Schema($this->connection->driver()->schemaInspector($this->connection));
        $migration->{$method}($schema);

        return $this->connection->driver()->schemaCompiler()->compile($schema);
    }

    private function execute(SchemaPlan $plan): void
    {
        foreach ($plan->batches() as $batch) {
            foreach ($batch->commands() as $command) {
                $this->connection->execute($command);
            }
        }
    }
}
