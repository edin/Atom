<?php

declare(strict_types=1);

namespace Atom\Queue;

use Atom\Di\Injector;

final readonly class JobExecutor
{
    public function __construct(private JobRegistry $jobs, private Injector $injector)
    {
    }

    public function execute(JobEnvelope $envelope): void
    {
        $class = $this->jobs->resolve($envelope->type);
        $job = $class::fromPayload($envelope->payload);

        if (!is_callable([$job, "handle"])) {
            throw new QueueException("Job '{$class}' must define a public handle() method.");
        }

        $this->injector->invoke([$job, "handle"]);
    }
}
