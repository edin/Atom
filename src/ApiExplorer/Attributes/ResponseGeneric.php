<?php

declare(strict_types=1);

namespace Atom\ApiExplorer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class ResponseGeneric
{
    public function __construct(
        public string $name,
        public string $type
    ) {
    }
}
