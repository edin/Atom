<?php

declare(strict_types=1);

namespace Atom\Queue\Commands;

use Atom\Console\Attributes\ConsoleCommand;
use Atom\Console\ConsoleOutput;
use Atom\Publish\PublishBundle;
use Atom\Publish\Publisher;
use Atom\Queue\QueueInterface;
use Atom\Queue\QueueOptions;
use Atom\Queue\QueueWorker;
use Atom\Queue\WorkerResult;

final readonly class QueueCommands
{
    public function __construct(
        private QueueWorker $worker,
        private QueueInterface $queue,
        private QueueOptions $options,
        private Publisher $publisher,
        private ConsoleOutput $output
    ) {
    }

    #[ConsoleCommand("queue:once", "Process at most one queued job")]
    public function once(?string $queue = null): int
    {
        $result = $this->worker->runOnce($queue);
        $this->output->line($this->resultMessage($result));
        return $result === WorkerResult::Failed ? 1 : 0;
    }

    #[ConsoleCommand("queue:work", "Process queued jobs continuously")]
    public function work(?string $queue = null, int $maxJobs = 0, ?int $sleep = null): int
    {
        if (strtolower($this->options->driver) === "sync") {
            $this->output->errorLine("The sync queue does not require a worker.");
            return 1;
        }

        $processed = 0;
        while ($maxJobs <= 0 || $processed < $maxJobs) {
            $result = $this->worker->runOnce($queue);
            if ($result === WorkerResult::Empty) {
                sleep(max(1, $sleep ?? $this->options->sleep));
                continue;
            }

            $processed++;
            $this->output->line($this->resultMessage($result));
        }

        return 0;
    }

    #[ConsoleCommand("queue:failed", "List failed queued jobs")]
    public function failed(?string $queue = null): int
    {
        $failures = $this->queue->failed($queue ?? $this->options->queue);
        if ($failures === []) {
            $this->output->line("No failed jobs.");
            return 0;
        }

        foreach ($failures as $failure) {
            $this->output->line(
                $failure->job->id . "  " . $failure->job->type . "  " . $failure->exception
            );
        }
        return 0;
    }

    #[ConsoleCommand("queue:publish", "Publish the database queue migration")]
    public function publish(bool $force = false): int
    {
        $bundle = (new PublishBundle("queue", dirname(__DIR__) . "/Resources"))
            ->file("M0001_create_atom_queue_tables.php", "@migrations/M0001_create_atom_queue_tables.php");
        $result = $this->publisher->publish($bundle, $force);

        foreach ($result->published as $file) {
            $this->output->line("Published: " . $this->output->command($file));
        }
        foreach ($result->overwritten as $file) {
            $this->output->line("Overwritten: " . $this->output->command($file));
        }
        foreach ($result->skipped as $file) {
            $this->output->line("Skipped existing: " . $this->output->muted($file));
        }
        return 0;
    }

    private function resultMessage(WorkerResult $result): string
    {
        return match ($result) {
            WorkerResult::Empty => "No jobs available.",
            WorkerResult::Completed => "Job completed.",
            WorkerResult::Released => "Job released for retry.",
            WorkerResult::Failed => "Job failed permanently.",
        };
    }
}
