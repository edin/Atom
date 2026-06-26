<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\AttributeSpreadNode;
use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\FragmentNode;
use Atom\View\Ast\ViewNode;
use Atom\View\Render\ExpressionEvaluatorInterface;
use Atom\View\Render\ViewContext;
use Atom\View\Render\ViewRenderException;
use Closure;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionType;
use Throwable;

final readonly class ComponentHydrator
{
    public function __construct(private ExpressionEvaluatorInterface $evaluator)
    {
    }

    /**
     * @param Closure(array<int, ViewNode>, ViewContext): string $renderNodes
     */
    public function hydrate(ComponentInterface $component, ElementNode $node, ViewContext $context, Closure $renderNodes): void
    {
        $this->assignProperties($component, $this->evaluateAttributes($node, $context));
        $this->assignFragments($component, $node, $context, $renderNodes);
        $this->validateRequiredProperties($component);
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluateAttributes(ElementNode $node, ViewContext $context): array
    {
        $attributes = [];

        foreach ($node->attributes as $attribute) {
            if ($attribute instanceof AttributeSpreadNode) {
                $attributes = [...$attributes, ...$this->evaluateAttributeSpread($attribute, $context)];
                continue;
            }

            if (!$attribute instanceof AttributeNode) {
                throw new ViewRenderException("Unsupported attribute node " . $attribute::class . ".");
            }

            $value = $attribute->value instanceof ExpressionNode
                ? $this->evaluator->evaluate($attribute->value->expression, $context)
                : $attribute->value;

            if (is_string($value)) {
                $value = $this->interpolate($value, $context);
            }

            $attributes[$attribute->name] = $value;
        }

        return $attributes;
    }

    private function interpolate(string $value, ViewContext $context): string
    {
        if (!str_contains($value, "{{")) {
            return $value;
        }

        return preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s', function (array $match) use ($context): string {
            $expression = trim($match[1]);
            if ($expression === "") {
                throw new ViewRenderException("Attribute expression cannot be empty.");
            }

            return (string) $this->evaluator->evaluate($expression, $context);
        }, $value) ?? $value;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function assignProperties(ComponentInterface $component, array $values): void
    {
        $unassigned = [];

        foreach ($values as $name => $value) {
            if ($this->assignProperty($component, $this->propertyName($name), $value)) {
                continue;
            }

            $unassigned[$name] = $value;
        }

        if ($unassigned === []) {
            return;
        }

        if ($this->assignAttributeBag($component, $unassigned)) {
            return;
        }

        $attribute = array_key_first($unassigned);
        throw new ViewRenderException("Unknown component attribute '" . $component::class . "::{$attribute}'.");
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function assignAttributeBag(ComponentInterface $component, array $attributes): bool
    {
        $reflection = new ReflectionObject($component);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || !$this->acceptsAttributeBag($property->getType())) {
                continue;
            }

            try {
                $property->setValue($component, new AttributeBag($attributes));
            } catch (Throwable $exception) {
                throw new ViewRenderException("Failed to assign component attribute bag '{$property->getName()}'.", previous: $exception);
            }

            return true;
        }

        return false;
    }

    /**
     * @param Closure(array<int, ViewNode>, ViewContext): string $renderNodes
     */
    private function assignFragments(ComponentInterface $component, ElementNode $node, ViewContext $context, Closure $renderNodes): void
    {
        $content = [];

        foreach ($node->children as $child) {
            if ($child instanceof FragmentNode) {
                $this->assignProperty(
                    $component,
                    $this->propertyName($child->name),
                    new Fragment(fn(array $variables = []): string => $renderNodes($child->children, $context->with($variables)))
                );
                continue;
            }

            $content[] = $child;
        }

        if ($content !== []) {
            $this->assignProperty(
                $component,
                "content",
                new Fragment(fn(array $variables = []): string => $renderNodes($content, $context->with($variables)))
            );
        }
    }

    private function assignProperty(ComponentInterface $component, string $name, mixed $value): bool
    {
        $reflection = new ReflectionObject($component);
        if (!$reflection->hasProperty($name)) {
            return false;
        }

        $property = $reflection->getProperty($name);
        if (!$property->isPublic() || $property->isStatic()) {
            return false;
        }

        if ($this->acceptsFragment($property->getType())) {
            $value = $value instanceof Fragment ? $value : $this->fragmentFromValue($value);
        } elseif ($value instanceof Fragment) {
            return false;
        }

        try {
            $property->setValue($component, $value);
        } catch (Throwable $exception) {
            throw new ViewRenderException("Failed to assign component property '{$name}'.", previous: $exception);
        }

        return true;
    }

    private function validateRequiredProperties(ComponentInterface $component): void
    {
        $reflection = new ReflectionObject($component);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || $property->getType() === null || $property->getType()->allowsNull()) {
                continue;
            }

            if (!$property->isInitialized($component)) {
                throw new ViewRenderException(
                    "Required component property '{$reflection->getName()}::\${$property->getName()}' was not provided."
                );
            }
        }
    }

    private function acceptsFragment(?ReflectionType $type): bool
    {
        if (!$type instanceof ReflectionNamedType) {
            return false;
        }

        return $type->getName() === Fragment::class;
    }

    private function acceptsAttributeBag(?ReflectionType $type): bool
    {
        if (!$type instanceof ReflectionNamedType) {
            return false;
        }

        return $type->getName() === AttributeBag::class;
    }

    private function fragmentFromValue(mixed $value): Fragment
    {
        return new Fragment(static fn(): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"));
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateAttributeSpread(AttributeSpreadNode $node, ViewContext $context): array
    {
        $attributes = $this->evaluator->evaluate($node->expression, $context);
        if (!is_iterable($attributes)) {
            throw new ViewRenderException("Attribute spread '{$node->expression}' must evaluate to an iterable.");
        }

        $result = [];
        foreach ($attributes as $name => $value) {
            if (!is_string($name)) {
                throw new ViewRenderException("Attribute spread '{$node->expression}' must use string attribute names.");
            }

            $result[$name] = $value;
        }

        return $result;
    }

    private function propertyName(string $name): string
    {
        $name = str_replace(["-", "_"], " ", $name);
        $name = str_replace(" ", "", ucwords($name));

        return lcfirst($name);
    }
}
