<?php

declare(strict_types=1);

namespace Atom\Cache;

use Atom\Config\Options;

#[Options("CACHE_")]
final readonly class CacheOptions
{
    public function __construct(
        public string $directory = "@root/storage/cache",
        public string $prefix = "atom",
        public int $defaultTtl = 0
    ) {
    }
}
