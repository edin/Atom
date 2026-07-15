<?php

declare(strict_types=1);

namespace Atom\Http;

use Atom\Config\Options;

#[Options("REQUEST_BODY_")]
final readonly class RequestBodyLimitOptions
{
    public function __construct(public int $maxBytes = 10485760)
    {
    }
}
