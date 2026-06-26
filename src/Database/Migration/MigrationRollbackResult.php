<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

final readonly class MigrationRollbackResult
{
    /**
     * @param string[] $migrations
     */
    public function __construct(public array $migrations)
    {
    }

    public function count(): int
    {
        return count($this->migrations);
    }

    public function isEmpty(): bool
    {
        return $this->migrations === [];
    }
}
