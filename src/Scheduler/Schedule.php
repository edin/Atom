<?php

declare(strict_types=1);

namespace Atom\Scheduler;

use Atom\Queue\JobInterface;

final class Schedule
{
    /** @var ScheduledTask[] */
    private array $tasks = [];

    /** @param string[] $arguments */
    public function command(string $command, array $arguments = []): ScheduledCommand
    {
        $task = new ScheduledCommand($command, $arguments);
        $this->tasks[] = $task;
        return $task;
    }

    public function job(JobInterface $job, int $delay = 0, ?string $queue = null): ScheduledJob
    {
        $task = new ScheduledJob($job, $delay, $queue);
        $this->tasks[] = $task;
        return $task;
    }

    /** @return ScheduledTask[] */
    public function tasks(): array
    {
        return $this->tasks;
    }
}
