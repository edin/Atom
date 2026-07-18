<?php

declare(strict_types=1);

namespace Atom\Queue;

final readonly class SyncJobDispatcher implements JobDispatcherInterface
{
    public function __construct(private JobExecutor $executor, private QueueOptions $options)
    {
    }

    public function dispatch(JobInterface $job, int $delay = 0, ?string $queue = null): string
    {
        $envelope = JobEnvelope::for($job, $queue ?? $this->options->queue, $delay);
        $this->executor->execute($envelope);

        return $envelope->id;
    }
}
