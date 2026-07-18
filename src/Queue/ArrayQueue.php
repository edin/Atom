<?php

declare(strict_types=1);

namespace Atom\Queue;

use Throwable;

final class ArrayQueue implements QueueInterface
{
    /** @var array<string, JobEnvelope> */
    private array $pending = [];
    /** @var array<string, ReservedJob> */
    private array $reserved = [];
    /** @var FailedJob[] */
    private array $failures = [];

    public function push(JobEnvelope $job): void
    {
        $this->pending[$job->id] = $job;
    }

    public function reserve(string $queue, int $retryAfter): ?ReservedJob
    {
        $now = time();
        foreach ($this->reserved as $id => $reserved) {
            if (($reserved->job->reservedAt ?? $now) <= $now - $retryAfter) {
                $this->pending[$id] = $reserved->job->released($now);
                unset($this->reserved[$id]);
            }
        }

        $jobs = array_values($this->pending);
        usort($jobs, static fn(JobEnvelope $a, JobEnvelope $b): int => [$a->availableAt, $a->id] <=> [$b->availableAt, $b->id]);

        foreach ($jobs as $job) {
            if ($job->queue !== $queue || $job->availableAt > $now) {
                continue;
            }

            $reservation = bin2hex(random_bytes(16));
            $reserved = new ReservedJob($job->reserved($reservation, $now), $reservation);
            unset($this->pending[$job->id]);
            $this->reserved[$job->id] = $reserved;
            return $reserved;
        }

        return null;
    }

    public function complete(ReservedJob $job): void
    {
        $this->removeReservation($job);
    }

    public function release(ReservedJob $job, int $delay = 0): void
    {
        $this->removeReservation($job);
        $this->pending[$job->job->id] = $job->job->released(time() + max(0, $delay));
    }

    public function fail(ReservedJob $job, Throwable $exception): void
    {
        $this->removeReservation($job);
        $this->failures[] = new FailedJob($job->job, $exception::class . ": " . $exception->getMessage(), time());
    }

    public function failed(string $queue = "default"): array
    {
        return array_values(array_filter(
            $this->failures,
            static fn(FailedJob $failure): bool => $failure->job->queue === $queue
        ));
    }

    /** @return JobEnvelope[] */
    public function pending(): array
    {
        return array_values($this->pending);
    }

    private function removeReservation(ReservedJob $job): void
    {
        $current = $this->reserved[$job->job->id] ?? null;
        if ($current === null || $current->reservationId !== $job->reservationId) {
            throw new QueueException("Job '{$job->job->id}' is not reserved by this worker.");
        }

        unset($this->reserved[$job->job->id]);
    }
}
