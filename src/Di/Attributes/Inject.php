<?php

declare(strict_types=1);

namespace Atom\Di\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
final readonly class Inject
{
    public function __construct(public string $token)
    {
    }
}
