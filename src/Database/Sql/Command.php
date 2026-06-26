<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final readonly class Command
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $sql,
        public array $parameters = []
    ) {
    }
}
