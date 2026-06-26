<?php

declare(strict_types=1);

namespace Atom\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class ConsoleCommand
{
    public function __construct(
        public string $name,
        public string $description = ""
    ) {
    }
}

