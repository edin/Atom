<?php

declare(strict_types=1);

namespace Atom\View\Ast;

final readonly class ElementNode implements ViewNode
{
    /**
     * @param array<int, AttributeNode|AttributeSpreadNode> $attributes
     * @param ViewNode[] $children
     */
    public function __construct(
        public string $name,
        public array $attributes = [],
        public array $children = [],
        public bool $selfClosing = false
    ) {
    }
}
