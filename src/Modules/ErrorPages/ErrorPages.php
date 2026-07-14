<?php

declare(strict_types=1);

namespace Atom\Modules\ErrorPages;

final readonly class ErrorPages
{
    public static function module(): ErrorPagesModule
    {
        return new ErrorPagesModule();
    }

    public static function debug(bool $debug = true): ErrorPagesModule
    {
        return new ErrorPagesModule(new ErrorPagesOptions($debug));
    }
}
