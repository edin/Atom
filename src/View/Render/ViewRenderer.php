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
        if ($this->components->has($node->name) || is_a($node->name, ComponentInterface::class, true)) {
            return $this->renderComponent($node, $context);
        }

        $attributes = $this->renderAttributes($node, $context);

        if ($node->selfClosing) {
            return "<{$node->name}{$attributes} />";
        }

        return "<{$node->name}{$attributes}>"
            . $this->renderNodes($node->children, $context)
            . "</{$node->name}>";
    }

    private function renderComponent(ElementNode $node, ViewContext $context): string
    {
        $className = $this->components->has($node->name)
            ? $this->components->get($node->name)
            : $node->name;
        $component = $this->componentFactory->create($className);

        $this->componentHydrator()->hydrate(
            $component,
            $node,
            $context,
            fn(array $nodes, ViewContext $context): string => $this->renderNodes($nodes, $context)
        );

        return $this->renderComponentResult($component->render(), $context);
    }

    private function renderComponentResult(mixed $result, ViewContext $context): string
    {
        if (is_string($result)) {
            return $result;
        }

        if ($result instanceof TemplateNode) {
            return $this->renderNodes($result->children, $context);
        }

        if ($result instanceof ViewNode) {
            return $this->renderNode($result, $context);
        }

        if (is_array($result)) {
            foreach ($result as $node) {
                if (!$node instanceof ViewNode) {
                    throw new ViewRenderException("Component returned an array with unsupported value.");
                }
            }

            return $this->renderNodes($result, $context);
        }

        throw new ViewRenderException("Component returned unsupported render result.");
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
        return $this->componentHydrator ?? new ComponentHydrator($this->evaluator);
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }
}
