<?php

declare(strict_types=1);

namespace Atom\Hydrator\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class FromRoute implements SourceAttributeInterface
{
    public function __construct(public ?string $name = null)
    {
    }

    public function source(): string
    {
        return "route";
    }

    public function name(): ?string
    {
        return $this->name;
    }
}
