<?php

declare(strict_types=1);

namespace Atom\Modules\Logging;

final readonly class Logging
{
    public static function module(): LoggingModule
    {
        return new LoggingModule();
    }

    public static function file(string $path): LoggingModule
    {
        return new LoggingModule(new LoggingOptions($path));
    }
}
