<?php

declare(strict_types=1);

namespace Atom\Config;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Options
{
    public function __construct(public string $prefix = "")
    {
    }
}
