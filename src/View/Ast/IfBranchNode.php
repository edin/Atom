<?php

declare(strict_types=1);

namespace Atom\View\Ast;

final readonly class IfBranchNode
{
    /**
     * @param ViewNodeInterface[] $children
     */
    public function __construct(
        public string $condition,
        public array $children = []
    ) {
    }
}
