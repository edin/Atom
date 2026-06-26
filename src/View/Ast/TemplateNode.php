<?php

declare(strict_types=1);

namespace Atom\View\Ast;

final readonly class TemplateNode
{
    /**
     * @param ViewNode[] $children
     */
    public function __construct(public array $children = [])
    {
    }
}
