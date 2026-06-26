<?php

declare(strict_types=1);

namespace Atom\Hydrator\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class FromHeader implements SourceAttributeInterface
{
    public function __construct(public ?string $name = null)
    {
    }

    public function source(): string
    {
        return "header";
    }
}
