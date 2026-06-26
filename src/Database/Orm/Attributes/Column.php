<?php

declare(strict_types=1);

namespace Atom\Database\Orm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly bool $select = true,
        public readonly bool $insert = true,
        public readonly bool $update = true,
        public readonly bool $nullable = false,
        public readonly ?string $converter = null,
        public readonly ?string $onInsert = null,
        public readonly ?string $onUpdate = null
    ) {
    }
}
