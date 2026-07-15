<?php

declare(strict_types=1);

namespace Atom\View\Ast;

final readonly class IfNode implements ViewNodeInterface
{
    public string $condition;

    /** @var ViewNodeInterface[] */
    public array $then;

    /**
     * @param IfBranchNode[] $branches
     * @param ViewNodeInterface[] $else
     */
    public function __construct(
        public array $branches,
        public array $else = []
    ) {
        $firstBranch = $branches[0] ?? throw new \InvalidArgumentException("If node requires at least one branch.");

        $this->condition = $firstBranch->condition;
        $this->then = $firstBranch->children;
    }
}
