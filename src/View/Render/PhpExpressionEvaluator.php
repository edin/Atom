<?php

declare(strict_types=1);

namespace Atom\View\Render;

use Throwable;

final readonly class PhpExpressionEvaluator implements ExpressionEvaluatorInterface
{
    public function evaluate(string $expression, ViewContext $context): mixed
    {
        try {
            return (static function () use ($expression, $context): mixed {
                extract($context->variables(), EXTR_SKIP);

                return eval("return {$expression};");
            })();
        } catch (Throwable $exception) {
            throw new ViewRenderException("Failed to evaluate expression '{$expression}'.", previous: $exception);
        }
    }
}
