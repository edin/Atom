<?php

declare(strict_types=1);

namespace Atom\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Get extends HttpRoute
{
    public function __construct(string $path)
    {
        parent::__construct("GET", $path);
    }
}
