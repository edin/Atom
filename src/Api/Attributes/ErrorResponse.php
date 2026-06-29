<?php

declare(strict_types=1);

namespace Atom\Api\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class ErrorResponse
{
    public function __construct(
        public int $status,
        public string $type,
        public ?string $description = null
    ) {
    }
}
