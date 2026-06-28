<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Http\Request;
use Atom\Hydrator\Exception\HydrationException;
use Atom\Hydrator\HydrationContext;
use Atom\Hydrator\HydrationPlanFactory;
use Atom\Hydrator\ValueCoercer;
use Atom\Router\MatchedRoute;

final readonly class PageInputHydrator implements PageInputHydratorInterface
{
    public function __construct(
        private HydrationPlanFactory $plans = new HydrationPlanFactory(),
        private ValueCoercer $coercer = new ValueCoercer()
    ) {
    }

    public function hydrate(Page $page, Request $request, MatchedRoute $route): void
    {
        $className = $page::class;
        $context = HydrationContext::fromRequest($request, $route->getRouteParams());

        foreach ($this->plans->for($className)->properties as $property) {
            if ($property->source === null) {
                continue;
            }

            $value = $context->get($property->source, $property->sourceName);
            if ($value === null && $property->hasDefaultValue) {
                continue;
            }

            try {
                $property->setValue($page, $this->coercer->coerce($value, $property, $className));
            } catch (HydrationException $exception) {
                throw new PageActionException(
                    "Unable to hydrate page input {$className}::{$property->name}. " . $exception->getMessage(),
                    previous: $exception
                );
            }
        }
    }
}
