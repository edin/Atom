<?php

declare(strict_types=1);

namespace Atom\Http;

use Atom\Config\Options;

#[Options("TRUSTED_")]
final readonly class TrustedProxyOptions
{
    public function __construct(public string $proxies = "")
    {
    }
}
