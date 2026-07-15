<?php

declare(strict_types=1);

namespace Atom\Logging;

final readonly class NullLogger implements LoggerInterface
{
    public function info(string $message, array $context = []): void
    {
    }

    public function error(string $message, array $context = []): void
    {
    }
}
