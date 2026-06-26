<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

final readonly class MigrationStatus
{
    public function __construct(
        public string $name,
        public bool $applied
    ) {
    }
}
