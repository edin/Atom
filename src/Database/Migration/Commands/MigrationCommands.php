<?php

declare(strict_types=1);

namespace Atom\Database\Migration\Commands;

use Atom\Console\Attributes\ConsoleCommand;
use Atom\Console\ConsoleOutput;
use Atom\Database\DatabaseConnection;
use Atom\Database\Schema\Reset\DatabaseResetterInterface;
use Atom\Database\Migration\MigrationCreator;
use Atom\Database\Migration\Migrator;
use Atom\Database\Seeder\SeederRunner;

final readonly class MigrationCommands
{
    public function __construct(
        private Migrator $migrator,
        private MigrationCreator $creator,
        private DatabaseConnection $connection,
        private DatabaseResetterInterface $resetter,
        private SeederRunner $seeders,
        private ConsoleOutput $output
    ) {
    }

    #[ConsoleCommand("make:migration", "Create a migration file")]
    public function make(string $name): int
    {
        $file = $this->creator->create($name);

        $this->output->line("Created migration: " . $this->output->command($file));

        return 0;
    }

    #[ConsoleCommand("migrate", "Run pending database migrations")]
    public function migrate(): int
    {
        $result = $this->migrator->run();

        $this->writeMigrated($result->migrations);

        return 0;
    }

    #[ConsoleCommand("migrate:rollback", "Roll back the latest migration batch")]
    public function rollback(int $steps = 1): int
    {
        $result = $this->migrator->rollback($steps);

        if ($result->isEmpty()) {
            $this->output->line("Nothing to roll back.");
            return 0;
        }

        foreach ($result->migrations as $migration) {
            $this->output->line("Rolled back: " . $this->output->command($migration));
        }

        return 0;
    }

    #[ConsoleCommand("migrate:fresh", "Drop all tables and rerun migrations")]
    public function fresh(bool $seed = false): int
    {
        $this->resetter->reset($this->connection);
        $this->output->line("Database reset.");

        $result = $this->migrator->run();
        $this->writeMigrated($result->migrations);

        if ($seed) {
            $seeded = $this->seeders->run();

            if ($seeded->isEmpty()) {
                $this->output->line("No seeders found.");
            }

            foreach ($seeded->seeders as $seeder) {
                $this->output->line("Seeded: " . $this->output->command($seeder));
            }
        }

        return 0;
    }

    #[ConsoleCommand("migrate:status", "Show database migration status")]
    public function status(): int
    {
        $statuses = $this->migrator->status();

        if ($statuses === []) {
            $this->output->line("No migrations found.");
            return 0;
        }

        $maxNameLength = 0;
        foreach ($statuses as $status) {
            $maxNameLength = max($maxNameLength, strlen($status->name));
        }

        foreach ($statuses as $status) {
            $state = $status->applied ? "ran" : "pending";
            $this->output->line(
                "  " . $this->output->command(str_pad($status->name, $maxNameLength)) .
                "  " . $this->output->muted($state)
            );
        }

        return 0;
    }

    #[ConsoleCommand("migrate:sql", "Show SQL for pending database migrations")]
    public function sql(): int
    {
        $migrations = $this->migrator->sql();

        if ($migrations === []) {
            $this->output->line("Nothing to migrate.");
            return 0;
        }

        foreach ($migrations as $migration => $commands) {
            $this->output->line($this->output->command($migration));

            foreach ($commands as $sql) {
                $this->output->line($sql . ";");
            }
        }

        return 0;
    }

    /**
     * @param string[] $migrations
     */
    private function writeMigrated(array $migrations): void
    {
        if ($migrations === []) {
            $this->output->line("Nothing to migrate.");
            return;
        }

        foreach ($migrations as $migration) {
            $this->output->line("Migrated: " . $this->output->command($migration));
        }
    }
}
