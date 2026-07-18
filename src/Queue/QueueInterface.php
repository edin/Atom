<?php

declare(strict_types=1);

namespace Atom\Queue;

use Throwable;

interface QueueInterface
{
    public function push(JobEnvelope $job): void;

    public function reserve(string $queue, int $retryAfter): ?ReservedJob;

    public function complete(ReservedJob $job): void;

    public function release(ReservedJob $job, int $delay = 0): void;

    public function fail(ReservedJob $job, Throwable $exception): void;

    /** @return FailedJob[] */
    public function failed(string $queue = "default"): array;
}
