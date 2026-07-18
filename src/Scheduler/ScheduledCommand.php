<?php

declare(strict_types=1);

namespace Atom\Scheduler;

final class ScheduledCommand extends ScheduledTask
{
    /** @param string[] $arguments */
    public function __construct(public readonly string $command, public readonly array $arguments = [])
    {
    }

    public function summary(): string
    {
        return trim($this->command . " " . implode(" ", $this->arguments));
    }
}
