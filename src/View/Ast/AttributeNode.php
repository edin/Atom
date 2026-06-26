<?php

declare(strict_types=1);

namespace Atom\View\Ast;

final readonly class AttributeNode
{
    public function __construct(
        public string $name,
        public string|bool $value = true,
        public bool $bound = false
    ) {
    }
}
