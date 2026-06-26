<?php

declare(strict_types=1);

namespace Atom\Hydrator\Attributes;

interface SourceAttributeInterface
{
    public function source(): string;
}
