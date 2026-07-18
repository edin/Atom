<?php

declare(strict_types=1);

namespace Atom\Queue;

interface JobDispatcherInterface
{
    public function dispatch(JobInterface $job, int $delay = 0, ?string $queue = null): string;
}
