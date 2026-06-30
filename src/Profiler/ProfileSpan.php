<?php

declare(strict_types=1);

namespace Atom\Profiler;

final class ProfileSpan
{
    private ?float $endedAt = null;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly Profiler $profiler,
        public readonly string $name,
        public readonly float $startedAt,
        public readonly array $metadata = []
    ) {
    }

    public function end(): self
    {
        if ($this->endedAt !== null) {
            return $this;
        }

        $this->endedAt = microtime(true);
        $this->profiler->record($this);

        return $this;
    }

    public function endedAt(): ?float
    {
        return $this->endedAt;
    }

    public function durationMs(): float
    {
        $endedAt = $this->endedAt ?? microtime(true);

        return ($endedAt - $this->startedAt) * 1000;
    }
}
