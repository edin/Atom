<?php

declare(strict_types=1);

namespace Atom\Modules\ErrorPages;

use Atom\Config\Options;

#[Options(prefix: "APP_")]
final readonly class ErrorPagesOptions
{
    public function __construct(public bool $debug = false)
    {
    }
}
