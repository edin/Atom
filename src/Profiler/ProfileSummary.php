<?php

declare(strict_types=1);

namespace Atom\Profiler;

final readonly class ProfileSummary
{
    public function __construct(
        public string $name,
        public int $count,
        public float $totalMs
    ) {
    }
}
