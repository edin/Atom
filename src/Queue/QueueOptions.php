<?php

declare(strict_types=1);

namespace Atom\Queue;

use Atom\Config\Options;

#[Options("QUEUE_")]
final readonly class QueueOptions
{
    public function __construct(
        public string $driver = "sync",
        public string $queue = "default",
        public string $directory = "@root/storage/queue",
        public int $retryAfter = 90,
        public int $retryDelay = 5,
        public int $maxAttempts = 3,
        public int $sleep = 1
    ) {
    }
}
