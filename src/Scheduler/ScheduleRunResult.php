<?php

declare(strict_types=1);

namespace Atom\Scheduler;

final readonly class ScheduleRunResult
{
    /** @param ScheduledTaskResult[] $tasks */
    public function __construct(public array $tasks = [])
    {
    }

    public function count(): int
    {
        return count($this->tasks);
    }

    public function failed(): int
    {
        return count(array_filter($this->tasks, static fn(ScheduledTaskResult $task): bool => !$task->successful));
    }
}
