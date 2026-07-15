<?php

declare(strict_types=1);

namespace Atom\Logging;

use Atom\Container;

final readonly class Log
{
    /**
     * @param array<string, mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::logger()->info($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::logger()->error($message, $context);
    }

    private static function logger(): LoggerInterface
    {
        return Container::has(LoggerInterface::class)
            ? Container::get(LoggerInterface::class)
            : new NullLogger();
    }
}
