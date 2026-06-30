<?php

declare(strict_types=1);

namespace Atom\Config;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class FromEnv
{
    public function __construct(public string $name)
    {
    }
}
