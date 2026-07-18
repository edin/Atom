<?php

declare(strict_types=1);

namespace Atom\Queue;

final readonly class FailedJob
{
    public function __construct(
        public JobEnvelope $job,
        public string $exception,
        public int $failedAt
    ) {
    }
}
