<?php

declare(strict_types=1);

namespace Atom\ApiExplorer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ArrayOf
{
    public function __construct(public string $type)
    {
    }
}
