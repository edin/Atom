<?php

declare(strict_types=1);

namespace Atom\Database\Seeder;

final readonly class SeederDefinition
{
    /**
     * @param callable(): Seeder $factory
     */
    public function __construct(
        public string $name,
        private mixed $factory
    ) {
    }

    public function create(): Seeder
    {
        $seeder = ($this->factory)();

        if (!$seeder instanceof Seeder) {
            throw new \RuntimeException("Seeder '{$this->name}' must create a Seeder instance.");
        }

        return $seeder;
    }
}
