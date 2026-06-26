<?php

declare(strict_types=1);

namespace Atom\Page;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class PageAction
{
    public function __construct(
        public string $name,
        public string $method = "post"
    ) {
    }
}
