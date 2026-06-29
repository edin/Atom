<?php

declare(strict_types=1);

namespace Atom\Api\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ArrayOf
{
    public function __construct(public ?string $type = null)
    {
    }
}
