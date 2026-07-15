<?php

declare(strict_types=1);

namespace Atom\Http;

use Atom\Config\Options;

#[Options("CORS_")]
final readonly class CorsOptions
{
    public function __construct(
        public string $allowedOrigins = "",
        public string $allowedMethods = "GET,POST,PUT,PATCH,DELETE,OPTIONS",
        public string $allowedHeaders = "Content-Type,Authorization,X-CSRF-Token,X-Requested-With",
        public string $exposedHeaders = "",
        public bool $allowCredentials = false,
        public int $maxAge = 0
    ) {
    }
}
