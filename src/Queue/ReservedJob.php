<?php

declare(strict_types=1);

namespace Atom\Queue;

final readonly class ReservedJob
{
    public function __construct(public JobEnvelope $job, public string $reservationId)
    {
    }
}
