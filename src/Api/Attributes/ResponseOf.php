<?php

declare(strict_types=1);

namespace Atom\Api\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class ResponseOf
{
    public function __construct(public string $type)
    {
    }
}
