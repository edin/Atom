<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Http\Request;
use Atom\Hydrator\Exception\HydrationException;
use Atom\Hydrator\HydrationContext;
use Atom\Hydrator\HydrationTarget;
use Atom\Hydrator\ValueCoercer;
use Atom\Router\MatchedRoute;
use ReflectionMethod;

final readonly class PageActionParameterBinder
{
    public function __construct(private ValueCoercer $coercer = new ValueCoercer())
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function bind(
        Page $page,
        Request $request,
        MatchedRoute $route,
        PageActionCall $call,
        ReflectionMethod $method
    ): array {
        $this->assertArgumentCount($call, $method);

        $context = HydrationContext::fromRequest($request, $route->getRouteParams());
        $parameters = [];

        foreach ($method->getParameters() as $index => $parameter) {
            $target = HydrationTarget::fromParameter($parameter);

            if (array_key_exists($index, $call->arguments)) {
                $parameters[$target->name] = $this->coerce($call->arguments[$index], $target, $page, $method);
                continue;
            }

            $value = $this->valueFromContext($context, $target);
            if ($value === null && $target->source === null && !$this->canBindImplicitly($target)) {
                continue;
            }

            if ($value === null && !$this->shouldBindNull($target)) {
                continue;
            }

            $parameters[$target->name] = $this->coerce($value, $target, $page, $method);
        }

        return $parameters;
    }

    private function valueFromContext(HydrationContext $context, HydrationTarget $target): mixed
    {
        if ($target->source !== null) {
            return $context->get($target->source, $target->sourceName);
        }

        if (!$this->canBindImplicitly($target)) {
            return null;
        }

        if (array_key_exists($target->name, $context->route)) {
            return $context->route[$target->name];
        }

        if (array_key_exists($target->name, $context->body)) {
            return $context->body[$target->name];
        }

        if (array_key_exists($target->name, $context->query)) {
            return $context->query[$target->name];
        }

        return null;
    }

    private function canBindImplicitly(HydrationTarget $target): bool
    {
        return $target->typeName === null || $target->isBuiltin;
    }

    private function shouldBindNull(HydrationTarget $target): bool
    {
        return $target->source !== null || !$target->hasDefaultValue;
    }

    private function coerce(mixed $value, HydrationTarget $target, Page $page, ReflectionMethod $method): mixed
    {
        try {
            return $this->coercer->coerce($value, $target, $page::class);
        } catch (HydrationException $exception) {
            throw new PageActionException(
                "Unable to bind parameter '{$target->name}' for page action " .
                $method->getDeclaringClass()->getName() . "::" . $method->getName() . "(). " .
                $exception->getMessage(),
                previous: $exception
            );
        }
    }

    private function assertArgumentCount(PageActionCall $call, ReflectionMethod $method): void
    {
        if (count($call->arguments) <= $method->getNumberOfParameters()) {
            return;
        }

        throw new PageActionException(
            "Page action '{$call->fullName()}' was called with " . count($call->arguments) .
            " argument(s), but " . $method->getDeclaringClass()->getName() . "::" . $method->getName() .
            "() accepts " . $method->getNumberOfParameters() . "."
        );
    }
}
