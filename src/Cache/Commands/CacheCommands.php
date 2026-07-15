<?php

declare(strict_types=1);

namespace Atom\Cache\Commands;

use Atom\Cache\CacheInterface;
use Atom\Cache\PrunableCacheInterface;
use Atom\Console\Attributes\ConsoleCommand;
use Atom\Console\ConsoleOutput;

final readonly class CacheCommands
{
    public function __construct(
        private CacheInterface $cache,
        private ConsoleOutput $output
    ) {
    }

    #[ConsoleCommand("cache:clear", "Remove all entries from the active cache namespace")]
    public function clear(): int
    {
        $this->cache->clear();
        $this->output->line("Cache cleared.");

        return 0;
    }

    #[ConsoleCommand("cache:prune", "Remove expired and corrupt cache entries")]
    public function prune(): int
    {
        if (!$this->cache instanceof PrunableCacheInterface) {
            $this->output->errorLine("The active cache driver does not support pruning.");
            return 1;
        }

        $removed = $this->cache->prune();
        $label = $removed === 1 ? "entry" : "entries";
        $this->output->line("Pruned {$removed} cache {$label}.");

        return 0;
    }
}
