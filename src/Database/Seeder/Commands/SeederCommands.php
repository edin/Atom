<?php

declare(strict_types=1);

namespace Atom\Database\Seeder\Commands;

use Atom\Console\Attributes\ConsoleCommand;
use Atom\Console\ConsoleOutput;
use Atom\Database\Seeder\SeederCreator;
use Atom\Database\Seeder\SeederRunner;

final readonly class SeederCommands
{
    public function __construct(
        private SeederRunner $seeders,
        private SeederCreator $creator,
        private ConsoleOutput $output
    ) {
    }

    #[ConsoleCommand("make:seeder", "Create a seeder file")]
    public function make(string $name): int
    {
        $file = $this->creator->create($name);

        $this->output->line("Created seeder: " . $this->output->command($file));

        return 0;
    }

    #[ConsoleCommand("db:seed", "Run database seeders")]
    public function seed(): int
    {
        $result = $this->seeders->run();

        if ($result->isEmpty()) {
            $this->output->line("No seeders found.");
            return 0;
        }

        foreach ($result->seeders as $seeder) {
            $this->output->line("Seeded: " . $this->output->command($seeder));
        }

        return 0;
    }
}
