<?php

declare(strict_types=1);

namespace Atom\View\Ast;

final readonly class ExpressionNode implements ViewNode
{
    public function __construct(public string $expression)
    {
    }
}
