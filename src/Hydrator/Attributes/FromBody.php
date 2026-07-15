<?php

declare(strict_types=1);

namespace Atom\Hydrator\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class FromBody implements SourceAttributeInterface
{
    public function __construct(public ?string $name = null)
    {
    }

    public function source(): string
    {
        return "body";
    }

    public function name(): ?string
    {
        return $this->name;
    }
}
