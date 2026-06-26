<?php

declare(strict_types=1);

namespace Atom\View\Ast;

final readonly class AttributeSpreadNode
{
    public function __construct(public string $expression)
    {
    }
}
