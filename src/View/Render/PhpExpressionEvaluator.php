<?php

declare(strict_types=1);

namespace Atom\View\Render;

use Throwable;

final readonly class PhpExpressionEvaluator implements ExpressionEvaluatorInterface
{
    public function evaluate(string $expression, ViewContext $context): mixed
    {
        try {
            $variables = $context->variables();
            $thisObject = $variables["this"] ?? null;
            unset($variables["this"]);

            $evaluate = function () use ($expression, $variables): mixed {
                extract($variables, EXTR_SKIP);

                return eval("return {$expression};");
            };

            if (is_object($thisObject)) {
                $evaluate = $evaluate->bindTo($thisObject, $thisObject::class);
            }

            return $evaluate();
        } catch (Throwable $exception) {
            throw new ViewRenderException("Failed to evaluate expression '{$expression}'.", previous: $exception);
        }
    }
}
