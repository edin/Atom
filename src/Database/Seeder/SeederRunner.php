<?php

declare(strict_types=1);

namespace Atom\Database\Seeder;

use Atom\Di\Injector;
use RuntimeException;

final readonly class SeederRunner
{
    public function __construct(
        private Injector $injector,
        private SeederDiscovery $discovery,
        private SeederOptions $options
    ) {
    }

    /**
     * @return SeederDefinition[]
     */
    public function all(): array
    {
        return $this->discovery->discover($this->options);
    }

    public function run(): SeederRunResult
    {
        $seeded = [];

        foreach ($this->all() as $definition) {
            $seeder = $definition->create();
            if (!method_exists($seeder, "run")) {
                throw new RuntimeException("Seeder '{$definition->name}' must define a run method.");
            }

            $this->injector->invoke([$seeder, "run"]);
            $seeded[] = $definition->name;
        }

        return new SeederRunResult($seeded);
    }
}
