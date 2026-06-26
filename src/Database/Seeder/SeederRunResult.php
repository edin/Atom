<?php

declare(strict_types=1);

namespace Atom\Database\Seeder;

final readonly class SeederRunResult
{
    /**
     * @param string[] $seeders
     */
    public function __construct(public array $seeders)
    {
    }

    public function count(): int
    {
        return count($this->seeders);
    }

    public function isEmpty(): bool
    {
        return $this->seeders === [];
    }
}
