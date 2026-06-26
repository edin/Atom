<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\AttributeSpreadNode;
use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\FragmentNode;
use Atom\View\Ast\TextNode;
use Atom\View\Ast\ViewNode;
use Atom\View\Render\ExpressionEvaluatorInterface;
use Atom\View\Render\ViewContext;
use Atom\View\Render\ViewRenderException;
use Closure;
use ReflectionAttribute;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionType;
use Throwable;

final readonly class ComponentHydrator
{
    public function __construct(
        private ExpressionEvaluatorInterface $evaluator,
        private ComponentFactoryInterface $componentFactory = new NewComponentFactory()
    )
    {
    }

    /**
     * @param Closure(array<int, ViewNode>, ViewContext): string $renderNodes
     */
    public function hydrate(ComponentInterface $component, ElementNode $node, ViewContext $context, Closure $renderNodes): void
    {
        $this->assignProperties($component, $node, $this->evaluateAttributes($node, $context));
        $remainingChildren = $this->assignChildren($component, $node, $context, $renderNodes);
        $this->assignFragments($component, $node, $remainingChildren, $context, $renderNodes);
        $this->validateRequiredProperties($component, $node);
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
    private function assignProperties(ComponentInterface $component, ElementNode $node, array $values): void
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
        $property = $this->propertyName($attribute);
        $componentClass = $component::class;

        throw new ViewRenderException(
            "Unknown attribute '{$attribute}' on component {$componentClass} rendered from <{$node->name}>. "
            . "Add public property \${$property}, add an AttributeBag property, or remove the attribute."
        );
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
     * @return ViewNode[]
     */
    private function assignChildren(ComponentInterface $component, ElementNode $node, ViewContext $context, Closure $renderNodes): array
    {
        $targets = $this->childTargets($component);
        if ($targets === []) {
            return $node->children;
        }

        $remaining = [];

        foreach ($node->children as $child) {
            if (!$child instanceof ElementNode || !isset($targets[$child->name])) {
                $remaining[] = $child;
                continue;
            }

            $target = $targets[$child->name];
            $childComponent = $this->componentFactory->create($target["type"]);
            $this->hydrate($childComponent, $child, $context, $renderNodes);
            $this->appendChildComponent($component, $target["property"], $childComponent);
        }

        return $remaining;
    }

    /**
     * @return array<string, array{property: string, type: class-string<ComponentInterface>}>
     */
    private function childTargets(ComponentInterface $component): array
    {
        $targets = [];
        $reflection = new ReflectionObject($component);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            foreach ($property->getAttributes(Children::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var Children $children */
                $children = $attribute->newInstance();
                if ($children->tag === "") {
                    throw new ViewRenderException(
                        "Child component tag cannot be empty on {$reflection->getName()}::\${$property->getName()}."
                    );
                }

                if (!is_a($children->type, ComponentInterface::class, true)) {
                    throw new ViewRenderException(
                        "Child component '{$children->tag}' mapped by {$reflection->getName()}::\${$property->getName()} "
                        . "must implement " . ComponentInterface::class . "."
                    );
                }

                if (isset($targets[$children->tag])) {
                    throw new ViewRenderException(
                        "Child component tag '{$children->tag}' is mapped more than once on {$reflection->getName()}."
                    );
                }

                $targets[$children->tag] = [
                    "property" => $property->getName(),
                    "type" => $children->type,
                ];
            }
        }

        return $targets;
    }

    private function appendChildComponent(ComponentInterface $component, string $propertyName, ComponentInterface $child): void
    {
        $reflection = new ReflectionObject($component);
        $property = $reflection->getProperty($propertyName);

        $children = $property->isInitialized($component) ? $property->getValue($component) : [];
        if (!is_array($children)) {
            throw new ViewRenderException(
                "Child component property '{$reflection->getName()}::\${$propertyName}' must be an array because it uses #[Children]."
            );
        }

        $children[] = $child;

        try {
            $property->setValue($component, $children);
        } catch (Throwable $exception) {
            throw new ViewRenderException("Failed to assign child component property '{$propertyName}'.", previous: $exception);
        }
    }

    /**
     * @param ViewNode[] $children
     * @param Closure(array<int, ViewNode>, ViewContext): string $renderNodes
     */
    private function assignFragments(ComponentInterface $component, ElementNode $node, array $children, ViewContext $context, Closure $renderNodes): void
    {
        $content = [];
        $componentClass = $component::class;

        foreach ($children as $child) {
            if ($child instanceof FragmentNode) {
                $propertyName = $this->propertyName($child->name);
                if (!$this->assignProperty(
                    $component,
                    $propertyName,
                    new Fragment(fn(array $variables = []): string => $renderNodes($child->children, $context->with($variables)))
                )) {
                    throw new ViewRenderException(
                        "Unknown fragment <{$child->owner}.{$child->name}> on component {$componentClass}. "
                        . "Add public ?Fragment \${$propertyName} to receive it."
                    );
                }

                continue;
            }

            $content[] = $child;
        }

        if ($content !== []) {
            if (!$this->assignProperty(
                $component,
                "content",
                new Fragment(fn(array $variables = []): string => $renderNodes($content, $context->with($variables)))
            )) {
                $preview = $this->firstContentNode($content);
                throw new ViewRenderException(
                    "Component {$componentClass} rendered from <{$node->name}> received child content{$preview}, "
                    . "but it does not declare public ?Fragment \$content."
                );
            }
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

    private function validateRequiredProperties(ComponentInterface $component, ElementNode $node): void
    {
        $reflection = new ReflectionObject($component);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || $property->getType() === null || $property->getType()->allowsNull()) {
                continue;
            }

            if (!$property->isInitialized($component)) {
                throw new ViewRenderException(
                    "Required property {$reflection->getName()}::\${$property->getName()} was not provided for <{$node->name}>."
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

    /**
     * @param ViewNode[] $content
     */
    private function firstContentNode(array $content): string
    {
        foreach ($content as $node) {
            if ($node instanceof TextNode) {
                $text = trim($node->text);
                if ($text === "") {
                    continue;
                }

                return " starting with text '" . substr($text, 0, 30) . "'";
            }

            if ($node instanceof ElementNode) {
                return " starting with <{$node->name}>";
            }

            if ($node instanceof ExpressionNode) {
                return " starting with expression '{{ {$node->expression} }}'";
            }
        }

        return "";
    }
}
