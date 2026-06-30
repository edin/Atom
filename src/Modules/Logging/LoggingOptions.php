<?php

declare(strict_types=1);

namespace Atom\Modules\Logging;

use Atom\Config\Options;

#[Options(prefix: "LOG_")]
final readonly class LoggingOptions
{
    public function __construct(
        public string $path = "storage/logs/app.log"
    ) {
    }
}
