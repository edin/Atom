<?php

declare(strict_types=1);

namespace Atom\Profiler;

final class Profiler
{
    /** @var list<ProfileSpan> */
    private array $spans = [];
    private readonly float $startedAt;

    public function __construct()
    {
        $this->startedAt = microtime(true);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function begin(string $name, array $metadata = []): ProfileSpan
    {
        return new ProfileSpan($this, $name, microtime(true), $metadata);
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @param array<string, mixed> $metadata
     * @return T
     */
    public function measure(string $name, callable $callback, array $metadata = []): mixed
    {
        $span = $this->begin($name, $metadata);

        try {
            return $callback();
        } finally {
            $span->end();
        }
    }

    public function record(ProfileSpan $span): void
    {
        $this->spans[] = $span;
    }

    /**
     * @return list<ProfileSpan>
     */
    public function spans(): array
    {
        return $this->spans;
    }

    public function count(string $name): int
    {
        return count(array_filter(
            $this->spans,
            static fn(ProfileSpan $span): bool => $span->name === $name
        ));
    }

    public function total(string $name): float
    {
        return array_reduce(
            $this->spans,
            static fn(float $total, ProfileSpan $span): float =>
                $span->name === $name ? $total + $span->durationMs() : $total,
            0.0
        );
    }

    /**
     * @return array<string, ProfileSummary>
     */
    public function summary(): array
    {
        $summary = [];

        foreach ($this->spans as $span) {
            $current = $summary[$span->name] ?? new ProfileSummary($span->name, 0, 0.0);
            $summary[$span->name] = new ProfileSummary(
                $span->name,
                $current->count + 1,
                $current->totalMs + $span->durationMs()
            );
        }

        return $summary;
    }

    public function totalMs(): float
    {
        return (microtime(true) - $this->startedAt) * 1000;
    }
}
