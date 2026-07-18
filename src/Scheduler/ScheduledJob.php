<?php

declare(strict_types=1);

namespace Atom\Scheduler;

use Atom\Queue\JobInterface;

final class ScheduledJob extends ScheduledTask
{
    public function __construct(
        public readonly JobInterface $job,
        public readonly int $delay = 0,
        public readonly ?string $queue = null
    ) {
    }

    public function summary(): string
    {
        return $this->job::type();
    }
}
