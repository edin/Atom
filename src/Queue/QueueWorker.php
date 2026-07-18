<?php

declare(strict_types=1);

namespace Atom\Queue;

use Throwable;

final readonly class QueueWorker
{
    public function __construct(
        private QueueInterface $queue,
        private JobExecutor $executor,
        private QueueOptions $options
    ) {
    }

    public function runOnce(?string $queue = null): WorkerResult
    {
        $reserved = $this->queue->reserve($queue ?? $this->options->queue, $this->options->retryAfter);
        if ($reserved === null) {
            return WorkerResult::Empty;
        }

        try {
            $this->executor->execute($reserved->job);
            $this->queue->complete($reserved);
            return WorkerResult::Completed;
        } catch (Throwable $exception) {
            if ($reserved->job->attempts >= max(1, $this->options->maxAttempts)) {
                $this->queue->fail($reserved, $exception);
                return WorkerResult::Failed;
            }

            $this->queue->release($reserved, max(0, $this->options->retryDelay));
            return WorkerResult::Released;
        }
    }
}
