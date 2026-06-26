<?php

declare(strict_types=1);

namespace Atom\Page;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class PageRoute
{
    public function __construct(
        public string $path,
        public ?string $name = null,
        public string|array $method = "GET"
    ) {
    }
}
