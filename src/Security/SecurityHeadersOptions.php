<?php

declare(strict_types=1);

namespace Atom\Security;

use Atom\Config\Options;

#[Options("SECURITY_HEADERS_")]
final readonly class SecurityHeadersOptions
{
    public function __construct(
        public bool $noSniff = true,
        public string $frameOptions = "SAMEORIGIN",
        public string $referrerPolicy = "strict-origin-when-cross-origin",
        public string $permissionsPolicy = "camera=(), microphone=(), geolocation=()",
        public string $contentSecurityPolicy = "",
        public string $contentSecurityPolicyReportOnly = "",
        public int $hstsMaxAge = 0,
        public bool $hstsIncludeSubDomains = true,
        public bool $hstsPreload = false
    ) {
    }
}
