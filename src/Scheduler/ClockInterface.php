<?php

declare(strict_types=1);

namespace Atom\Scheduler;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
