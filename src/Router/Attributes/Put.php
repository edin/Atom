<?php

declare(strict_types=1);

namespace Atom\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Put extends HttpRoute
{
    public function __construct(string $path)
    {
        parent::__construct("PUT", $path);
    }
}
