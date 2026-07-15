<?php

declare(strict_types=1);

namespace Atom\Http;

use Atom\Config\Options;

#[Options("RATE_LIMIT_")]
final readonly class RateLimitOptions
{
    public function __construct(
        public int $maxAttempts = 60,
        public int $windowSeconds = 60,
        public string $keyPrefix = "http",
        public bool $includePath = true,
        public bool $includeMethod = false,
        public bool $failOpen = true
    ) {
    }
}
