<?php

declare(strict_types=1);

namespace Atom\Hydrator;

interface ValueTransformerInterface
{
    public function transform(mixed $value): mixed;
}
