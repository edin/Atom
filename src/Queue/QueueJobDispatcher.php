<?php

declare(strict_types=1);

namespace Atom\Queue;

final readonly class QueueJobDispatcher implements JobDispatcherInterface
{
    public function __construct(private QueueInterface $queue, private QueueOptions $options)
    {
    }

    public function dispatch(JobInterface $job, int $delay = 0, ?string $queue = null): string
    {
        $envelope = JobEnvelope::for($job, $queue ?? $this->options->queue, $delay);
        $this->queue->push($envelope);

        return $envelope->id;
    }
}
