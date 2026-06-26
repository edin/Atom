<?php

declare(strict_types=1);

namespace Atom\Di\Exception;

use RuntimeException;
use Throwable;

class DependencyResolutionException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
