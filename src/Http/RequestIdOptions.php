<?php

declare(strict_types=1);

namespace Atom\Http;

use Atom\Config\Options;

#[Options("REQUEST_ID_")]
final readonly class RequestIdOptions
{
    public function __construct(
        public string $headerName = "X-Request-Id",
        public bool $trustIncoming = true,
        public int $maxLength = 128
    ) {
    }
}
