<?php

declare(strict_types=1);

namespace Atom\Queue;

use InvalidArgumentException;

final class JobRegistry
{
    /** @var array<string, class-string<JobInterface>> */
    private array $jobs = [];

    /** @param class-string<JobInterface> $job */
    public function register(string $job): self
    {
        if (!is_subclass_of($job, JobInterface::class)) {
            throw new InvalidArgumentException("Job '{$job}' must implement " . JobInterface::class . ".");
        }

        $type = $job::type();
        if (isset($this->jobs[$type]) && $this->jobs[$type] !== $job) {
            throw new InvalidArgumentException("Job type '{$type}' is already registered.");
        }

        $this->jobs[$type] = $job;
        return $this;
    }

    /** @return class-string<JobInterface> */
    public function resolve(string $type): string
    {
        return $this->jobs[$type] ?? throw new QueueException("Job type '{$type}' is not registered.");
    }
}
