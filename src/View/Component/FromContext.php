<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class FromContext
{
    public function __construct(public ?string $name = null)
    {
    }
}
