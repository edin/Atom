<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Atom\Page\Page;
use Atom\View\Html;
use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\AttributeSpreadNode;
use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\FragmentNode;
use Atom\View\Ast\TextNode;
use Atom\View\Ast\ViewNodeInterface;
use Atom\View\Render\ExpressionEvaluatorInterface;
use Atom\View\Render\ViewContext;
use Atom\View\Render\ViewRenderException;
use Closure;
use ReflectionAttribute;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionType;
use ReflectionUnionType;
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
     * @param Closure(array<int, ViewNodeInterface>, ViewContext): string $renderNodes
     */
    public function hydrate(ComponentInterface $component, ElementNode $node, ViewContext $context, Closure $renderNodes): void
    {
        $assignedProperties = $this->assignProperties($component, $node, $this->evaluateAttributes($node, $context));
        $this->assignContextAttributes($component, $context, $assignedProperties);
        $this->assignContextProperties($component, $context);
        $remainingChildren = $this->assignChildren($component, $node, $context, $renderNodes);
        $this->assignFragments($component, $node, $remainingChildren, $context, $renderNodes);
        $this->initializeAttributeBags($component);
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
    private function assignProperties(ComponentInterface $component, ElementNode $node, array $values): array
    {
        $unassigned = [];
        $assigned = [];

        foreach ($values as $name => $value) {
            $property = $this->propertyName($name);
            if ($this->assignProperty($component, $property, $value)) {
                $assigned[] = $property;
                continue;
            }

            $unassigned[$name] = $value;
        }

        if ($unassigned === []) {
            return $assigned;
        }

        if ($this->assignAttributeBag($component, $unassigned)) {
            return $assigned;
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
     * @param Closure(array<int, ViewNodeInterface>, ViewContext): string $renderNodes
     * @return ViewNodeInterface[]
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
     * @param ViewNodeInterface[] $children
     * @param Closure(array<int, ViewNodeInterface>, ViewContext): string $renderNodes
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
                    new TemplateFragment($child->children, fn(array $variables = []): string => $renderNodes($child->children, $context->with($variables)))
                )) {
                    throw new ViewRenderException(
                        "Unknown fragment <{$child->owner}.{$child->name}> on component {$componentClass}. "
                        . "Add public ?Fragment or ?TemplateFragment \${$propertyName} to receive it."
                    );
                }

                continue;
            }

            $content[] = $child;
        }

        if ($this->hasMeaningfulContent($content)) {
            if (!$this->assignProperty(
                $component,
                "content",
                new TemplateFragment($content, fn(array $variables = []): string => $renderNodes($content, $context->with($variables)))
            )) {
                $preview = $this->firstContentNode($content);
                throw new ViewRenderException(
                    "Component {$componentClass} rendered from <{$node->name}> received child content{$preview}, "
                    . "but it does not declare public ?Fragment or ?TemplateFragment \$content."
                );
            }
        }
    }

    /**
     * @param ViewNodeInterface[] $content
     */
    private function hasMeaningfulContent(array $content): bool
    {
        foreach ($content as $node) {
            if (!$node instanceof TextNode || trim($node->text) !== "") {
                return true;
            }
        }

        return false;
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

        $type = $property->getType();

        if ($this->acceptsTemplateFragment($type)) {
            $value = $value instanceof TemplateFragment ? $value : $this->templateFragmentFromValue($value);
        } elseif ($this->acceptsFragment($type)) {
            if ($value instanceof TemplateFragment) {
                $value = $value->fragment();
            } elseif (!$value instanceof Fragment && !$this->acceptsNativeValue($type, $value)) {
                $value = $this->fragmentFromValue($value);
            }
        } elseif ($value instanceof Fragment || $value instanceof TemplateFragment) {
            return false;
        }

        try {
            $property->setValue($component, $value);
        } catch (Throwable $exception) {
            throw new ViewRenderException("Failed to assign component property '{$name}'.", previous: $exception);
        }

        return true;
    }

    private function assignContextProperties(ComponentInterface $component, ViewContext $context): void
    {
        $page = $context->variables()["page"] ?? null;
        if (!$page instanceof Page) {
            return;
        }

        $reflection = new ReflectionObject($component);
        if (!$reflection->hasProperty("page")) {
            return;
        }

        $property = $reflection->getProperty("page");
        if (
            !$property->isPublic() ||
            $property->isStatic() ||
            $property->isInitialized($component) ||
            !$this->acceptsPage($property->getType())
        ) {
            return;
        }

        try {
            $property->setValue($component, $page);
        } catch (Throwable $exception) {
            throw new ViewRenderException("Failed to assign component page context.", previous: $exception);
        }
    }

    /**
     * @param string[] $assignedProperties
     */
    private function assignContextAttributes(ComponentInterface $component, ViewContext $context, array $assignedProperties): void
    {
        $reflection = new ReflectionObject($component);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || in_array($property->getName(), $assignedProperties, true)) {
                continue;
            }

            foreach ($property->getAttributes(FromContext::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var FromContext $fromContext */
                $fromContext = $attribute->newInstance();
                $name = $fromContext->name ?? $property->getName();
                if (!$context->has($name)) {
                    continue;
                }

                try {
                    $property->setValue($component, $context->get($name));
                } catch (Throwable $exception) {
                    throw new ViewRenderException(
                        "Failed to assign component context property '{$property->getName()}' from '{$name}'.",
                        previous: $exception
                    );
                }
            }
        }
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

    private function initializeAttributeBags(ComponentInterface $component): void
    {
        $reflection = new ReflectionObject($component);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (
                $property->isStatic() ||
                $property->isInitialized($component) ||
                !$this->acceptsAttributeBag($property->getType())
            ) {
                continue;
            }

            try {
                $property->setValue($component, new AttributeBag());
            } catch (Throwable $exception) {
                throw new ViewRenderException("Failed to initialize component attribute bag '{$property->getName()}'.", previous: $exception);
            }
        }
    }

    private function acceptsFragment(?ReflectionType $type): bool
    {
        foreach ($this->namedTypes($type) as $namedType) {
            if ($namedType->getName() === Fragment::class) {
                return true;
            }
        }

        return false;
    }

    private function acceptsTemplateFragment(?ReflectionType $type): bool
    {
        foreach ($this->namedTypes($type) as $namedType) {
            if ($namedType->getName() === TemplateFragment::class) {
                return true;
            }
        }

        return false;
    }

    private function acceptsAttributeBag(?ReflectionType $type): bool
    {
        if (!$type instanceof ReflectionNamedType) {
            return false;
        }

        return $type->getName() === AttributeBag::class;
    }

    private function acceptsPage(?ReflectionType $type): bool
    {
        if (!$type instanceof ReflectionNamedType) {
            return false;
        }

        return is_a($type->getName(), Page::class, true);
    }

    private function acceptsNativeValue(?ReflectionType $type, mixed $value): bool
    {
        foreach ($this->namedTypes($type) as $namedType) {
            if ($namedType->isBuiltin() && get_debug_type($value) === $namedType->getName()) {
                return true;
            }

            if (!$namedType->isBuiltin() && is_object($value) && is_a($value, $namedType->getName())) {
                return true;
            }
        }

        return $value === null && $type?->allowsNull() === true;
    }

    /**
     * @return ReflectionNamedType[]
     */
    private function namedTypes(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            return [$type];
        }

        if ($type instanceof ReflectionUnionType) {
            return array_values(array_filter(
                $type->getTypes(),
                static fn(ReflectionType $type): bool => $type instanceof ReflectionNamedType
            ));
        }

        return [];
    }

    private function fragmentFromValue(mixed $value): Fragment
    {
        return new Fragment(static fn(): string => Html::escape($value));
    }

    private function templateFragmentFromValue(mixed $value): TemplateFragment
    {
        $source = (string) $value;

        return new TemplateFragment(
            [new TextNode($source)],
            static fn(): string => Html::escape($source),
            $source
        );
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
     * @param ViewNodeInterface[] $content
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
