<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Http\Request;
use Atom\Hydrator\Exception\HydrationException;
use Atom\Hydrator\HydrationContext;
use Atom\Hydrator\HydrationPlanFactory;
use Atom\Hydrator\HydrationTarget;
use Atom\Hydrator\ValueCoercer;
use Atom\Router\MatchedRoute;
use ReflectionAttribute;

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
            if ($this->isFormModel($property)) {
                $this->hydrateFormModel($page, $property, $context, $className);
                continue;
            }

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

    private function isFormModel(HydrationTarget $property): bool
    {
        return $property->reflection->getAttributes(FormModel::class, ReflectionAttribute::IS_INSTANCEOF) !== [];
    }

    private function hydrateFormModel(Page $page, HydrationTarget $property, HydrationContext $context, string $pageClass): void
    {
        if ($property->typeName === "array") {
            $model = $property->property?->isInitialized($page) === true
                ? $property->property->getValue($page)
                : [];

            if (!is_array($model)) {
                $model = [];
            }

            foreach ($context->body as $name => $value) {
                if (str_starts_with((string) $name, "_")) {
                    continue;
                }

                if ($model !== [] && !array_key_exists($name, $model)) {
                    continue;
                }

                $model[$name] = $value;
            }

            $property->setValue($page, $model);
            return;
        }

        if ($property->typeName === null || $property->isBuiltin) {
            throw new PageActionException("Form model {$pageClass}::{$property->name} must be an object or array.");
        }

        $modelClass = $property->typeName;
        $model = $property->property?->isInitialized($page) === true
            ? $property->property->getValue($page)
            : null;

        if (!$model instanceof $modelClass) {
            $model = $this->plans->for($modelClass)->createInstance();
        }

        foreach ($this->plans->for($modelClass)->properties as $field) {
            $source = $field->source ?? "body";
            $sourceName = $field->sourceName;

            if (!$this->hasInputValue($context, $source, $sourceName)) {
                continue;
            }

            $value = $context->get($source, $sourceName);

            try {
                $field->setValue($model, $this->coercer->coerce($value, $field, $modelClass));
            } catch (HydrationException $exception) {
                throw new PageActionException(
                    "Unable to hydrate form model {$modelClass}::{$field->name}. " . $exception->getMessage(),
                    previous: $exception
                );
            }
        }

        $property->setValue($page, $model);
    }

    private function hasInputValue(HydrationContext $context, string $source, string $name): bool
    {
        return match ($source) {
            "body" => array_key_exists($name, $context->body),
            "query" => array_key_exists($name, $context->query),
            "route" => array_key_exists($name, $context->route),
            default => $context->get($source, $name) !== null,
        };
    }
}
