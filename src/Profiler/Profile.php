<?php

declare(strict_types=1);

namespace Atom\Profiler;

use Atom\Container;
use Throwable;

final class Profile
{
    private static ?Profiler $fallback = null;

    /**
     * @param array<string, mixed> $metadata
     */
    public static function begin(string $name, array $metadata = []): ProfileSpan
    {
        return self::profiler()->begin($name, $metadata);
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @param array<string, mixed> $metadata
     * @return T
     */
    public static function measure(string $name, callable $callback, array $metadata = []): mixed
    {
        return self::profiler()->measure($name, $callback, $metadata);
    }

    public static function profiler(): Profiler
    {
        try {
            if (Container::has(Profiler::class)) {
                return Container::get(Profiler::class);
            }
        } catch (Throwable) {
        }

        return self::$fallback ??= new Profiler();
    }

    public static function reset(): void
    {
        self::$fallback = null;
    }
}
