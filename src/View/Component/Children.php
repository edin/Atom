<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class Children
{
    /**
     * @param class-string<ComponentInterface> $type
     */
    public function __construct(
        public string $tag,
        public string $type
    ) {
    }
}
