<?php

declare(strict_types=1);

namespace Atom\Database\Orm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class PrimaryKey extends Column
{
    public function __construct(
        ?string $name = null,
        public readonly bool $autoIncrement = true
    ) {
        parent::__construct(
            name: $name,
            select: true,
            insert: !$autoIncrement,
            update: false
        );
    }
}
