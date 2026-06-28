<?php

declare(strict_types=1);

namespace Atom\View\Render;

use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\FragmentNode;
use Atom\View\Ast\ForEachNode;
use Atom\View\Ast\IfNode;
use Atom\View\Ast\RawTextNode;
use Atom\View\Ast\TemplateNode;
use Atom\View\Ast\TextNode;
use Atom\View\Ast\ViewNode;
use Atom\View\Component\ComponentFactoryInterface;
use Atom\View\Component\ComponentHydrator;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use Atom\View\Component\NewComponentFactory;
use ReflectionObject;
use ReflectionProperty;

final readonly class ViewRenderer
{
    public function __construct(
        private ExpressionEvaluatorInterface $evaluator = new PhpExpressionEvaluator(),
        private ComponentRegistry $components = new ComponentRegistry(),
        private ComponentFactoryInterface $componentFactory = new NewComponentFactory(),
        private ?ComponentHydrator $componentHydrator = null
    )
    {
    }

    /**
     * @param array<string, mixed>|ViewContext $variables
     */
    public function render(TemplateNode $template, array|ViewContext $variables = []): string
    {
        $context = $variables instanceof ViewContext ? $variables : new ViewContext($variables);

        return $this->renderNodes($template->children, $context);
    }

    /**
     * @param ViewNode[] $nodes
     */
    private function renderNodes(array $nodes, ViewContext $context): string
    {
        $output = "";

        foreach ($nodes as $node) {
            $output .= $this->renderNode($node, $context);
        }

        return $output;
    }

    private function renderNode(ViewNode $node, ViewContext $context): string
    {
        return match (true) {
            $node instanceof TextNode => $node->text,
            $node instanceof RawTextNode => $node->text,
            $node instanceof ExpressionNode => $this->escape($this->evaluator->evaluate($node->expression, $context)),
            $node instanceof ElementNode => $this->renderElement($node, $context),
            $node instanceof FragmentNode => throw new ViewRenderException("Fragment '{$node->owner}.{$node->name}' cannot be rendered outside a component."),
            $node instanceof IfNode => $this->renderIf($node, $context),
            $node instanceof ForEachNode => $this->renderForEach($node, $context),
            default => throw new ViewRenderException("Unsupported view node " . $node::class . "."),
        };
    }

    private function renderElement(ElementNode $node, ViewContext $context): string
    {
        $componentClass = $this->resolveComponentClass($node);
        if ($componentClass !== null) {
            return $this->renderComponent($node, $context, $componentClass);
        }

        $attributes = $this->renderAttributes($node, $context);

        if ($node->selfClosing) {
            return "<{$node->name}{$attributes} />";
        }

        return "<{$node->name}{$attributes}>"
            . $this->renderNodes($node->children, $context)
            . "</{$node->name}>";
    }

    /**
     * @return class-string<ComponentInterface>|null
     */
    private function resolveComponentClass(ElementNode $node): ?string
    {
        if ($this->components->has($node->name)) {
            return $this->components->get($node->name);
        }

        if (is_a($node->name, ComponentInterface::class, true)) {
            return $node->name;
        }

        return null;
    }

    /**
     * @param class-string<ComponentInterface> $className
     */
    private function renderComponent(ElementNode $node, ViewContext $context, string $className): string
    {
        $component = $this->componentFactory->create($className);

        $this->componentHydrator()->hydrate(
            $component,
            $node,
            $context,
            fn(array $nodes, ViewContext $context): string => $this->renderNodes($nodes, $context)
        );

        return $this->renderComponentResult($component, $component->render(), $context);
    }

    private function renderComponentResult(ComponentInterface $component, mixed $result, ViewContext $context): string
    {
        $componentClass = $component::class;

        if (is_string($result)) {
            return $result;
        }

        $componentContext = $this->componentContext($component, $context);

        if ($result instanceof TemplateNode) {
            return $this->renderNodes($result->children, $componentContext);
        }

        if ($result instanceof ViewNode) {
            return $this->renderNode($result, $componentContext);
        }

        if (is_array($result)) {
            foreach ($result as $index => $node) {
                if (!$node instanceof ViewNode) {
                    throw new ViewRenderException(
                        "Component {$componentClass} returned an array with unsupported value at index '{$index}': "
                        . get_debug_type($node) . ". Expected every item to implement " . ViewNode::class . "."
                    );
                }
            }

            return $this->renderNodes($result, $componentContext);
        }

        throw new ViewRenderException(
            "Component {$componentClass} returned unsupported render result " . get_debug_type($result)
            . ". Expected string, " . TemplateNode::class . ", " . ViewNode::class . ", or array<ViewNode>."
        );
    }

    private function componentContext(ComponentInterface $component, ViewContext $context): ViewContext
    {
        return $context->with([
            "this" => $component,
            "component" => $component,
            "context" => new \Atom\View\Component\ComponentTemplateContext(),
            ...$this->publicProperties($component),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function publicProperties(ComponentInterface $component): array
    {
        $reflection = new ReflectionObject($component);
        $variables = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || !$property->isInitialized($component)) {
                continue;
            }

            $variables[$property->getName()] = $property->getValue($component);
        }

        return $variables;
    }

    private function renderIf(IfNode $node, ViewContext $context): string
    {
        foreach ($node->branches as $branch) {
            if ((bool) $this->evaluator->evaluate($branch->condition, $context)) {
                return $this->renderNodes($branch->children, $context);
            }
        }

        return $this->renderNodes($node->else, $context);
    }

    private function renderForEach(ForEachNode $node, ViewContext $context): string
    {
        $source = $this->evaluator->evaluate($node->source, $context);
        if (!is_iterable($source)) {
            throw new ViewRenderException("Expression '{$node->source}' must be iterable.");
        }

        $output = "";
        foreach ($source as $key => $value) {
            $variables = [$this->variableName($node->value) => $value];
            if ($node->key !== null) {
                $variables[$this->variableName($node->key)] = $key;
            }

            $output .= $this->renderNodes($node->children, $context->with($variables));
        }

        return $output;
    }

    private function renderAttributes(ElementNode $node, ViewContext $context): string
    {
        return $this->formatAttributes($this->componentHydrator()->evaluateAttributes($node, $context));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function formatAttributes(array $attributes): string
    {
        $output = "";

        foreach ($attributes as $name => $value) {
            if ($value === false || $value === null) {
                continue;
            }

            if ($value === true) {
                $output .= " " . $name;
                continue;
            }

            $output .= " " . $name . '="' . $this->escape($value) . '"';
        }

        return $output;
    }

    private function variableName(string $name): string
    {
        return ltrim($name, "$");
    }

    private function componentHydrator(): ComponentHydrator
    {
        return $this->componentHydrator ?? new ComponentHydrator($this->evaluator, $this->componentFactory);
    }

    private function escape(mixed $value): string
    {
        if ($value instanceof HtmlString) {
            return $value->toHtml();
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }
}
