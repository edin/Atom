<?php

declare(strict_types=1);

namespace Atom\View\Ast;

final readonly class ForEachNode implements ViewNodeInterface
{
    /**
     * @param ViewNodeInterface[] $children
     */
    public function __construct(
        public string $source,
        public string $value,
        public ?string $key = null,
        public array $children = []
    ) {
    }
}
