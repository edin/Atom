<?php

declare(strict_types=1);

namespace Atom\Scheduler;

final readonly class ScheduledTaskResult
{
    public function __construct(
        public ScheduledTask $task,
        public bool $successful,
        public string $output = ""
    ) {
    }
}
