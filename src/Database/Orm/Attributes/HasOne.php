<?php

declare(strict_types=1);

namespace Atom\Database\Orm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class HasOne
{
    /**
     * @param class-string $relatedClass
     */
    public function __construct(
        public string $relatedClass,
        public string $foreignKey,
        public string $localKey = "id"
    ) {
    }
}
