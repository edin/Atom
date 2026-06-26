<?php

declare(strict_types=1);

namespace Atom\Console;

/**
 * @implements \IteratorAggregate<int, ConsoleCommandSource>
 */
final class ConsoleCommandSources implements \IteratorAggregate
{
    /** @var ConsoleCommandSource[] */
    private array $sources = [];

    public function add(string $directory, string $namespace): self
    {
        $this->sources[] = new ConsoleCommandSource($directory, $namespace);
        return $this;
    }

    /**
     * @return ConsoleCommandSource[]
     */
    public function all(): array
    {
        return $this->sources;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->sources);
    }
}
