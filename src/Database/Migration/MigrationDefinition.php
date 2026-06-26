<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

final readonly class MigrationDefinition
{
    /**
     * @param callable(): Migration $factory
     */
    public function __construct(
        public string $name,
        private mixed $factory
    ) {
    }

    public function create(): Migration
    {
        $migration = ($this->factory)();

        if (!$migration instanceof Migration) {
            throw new \RuntimeException("Migration '{$this->name}' must create a Migration instance.");
        }

        return $migration;
    }
}
