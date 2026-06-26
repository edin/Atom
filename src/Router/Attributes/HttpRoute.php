<?php

declare(strict_types=1);

namespace Atom\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class HttpRoute
{
    public function __construct(
        public string|array $method,
        public string $path
    ) {
    }
}
