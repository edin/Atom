<?php

declare(strict_types=1);

namespace Atom\View\Render;

interface ExpressionEvaluatorInterface
{
    public function evaluate(string $expression, ViewContext $context): mixed;
}
