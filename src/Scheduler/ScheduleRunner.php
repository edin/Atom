<?php

declare(strict_types=1);

namespace Atom\Scheduler;

use Atom\Console\BufferedConsoleOutput;
use Atom\Console\ConsoleApplication;
use Atom\Queue\JobDispatcherInterface;
use Throwable;

final readonly class ScheduleRunner
{
    public function __construct(
        private Schedule $schedule,
        private ClockInterface $clock,
        private ConsoleApplication $console,
        private JobDispatcherInterface $jobs
    ) {
    }

    public function run(): ScheduleRunResult
    {
        $now = $this->clock->now();
        $results = [];

        foreach ($this->schedule->tasks() as $task) {
            if (!$task->isDue($now)) {
                continue;
            }

            try {
                $results[] = $this->execute($task);
            } catch (Throwable $exception) {
                $results[] = new ScheduledTaskResult($task, false, $exception->getMessage());
            }
        }

        return new ScheduleRunResult($results);
    }

    private function execute(ScheduledTask $task): ScheduledTaskResult
    {
        if ($task instanceof ScheduledJob) {
            $this->jobs->dispatch($task->job, $task->delay, $task->queue);
            return new ScheduledTaskResult($task, true);
        }

        if ($task instanceof ScheduledCommand) {
            $output = new BufferedConsoleOutput();
            $code = $this->console->run(["atom", $task->command, ...$task->arguments], $output);
            $contents = trim($output->output() . $output->errors());
            return new ScheduledTaskResult($task, $code === 0, $contents);
        }

        return new ScheduledTaskResult($task, false, "Unsupported scheduled task.");
    }
}
