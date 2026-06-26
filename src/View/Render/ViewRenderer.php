<?php

declare(strict_types=1);

namespace Atom\View\Render;

use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\AttributeSpreadNode;
use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\ForEachNode;
use Atom\View\Ast\IfNode;
use Atom\View\Ast\RawTextNode;
use Atom\View\Ast\TemplateNode;
use Atom\View\Ast\TextNode;
use Atom\View\Ast\ViewNode;

final readonly class ViewRenderer
{
    public function __construct(private ExpressionEvaluatorInterface $evaluator = new PhpExpressionEvaluator())
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
            $node instanceof IfNode => $this->renderIf($node, $context),
            $node instanceof ForEachNode => $this->renderForEach($node, $context),
            default => throw new ViewRenderException("Unsupported view node " . $node::class . "."),
        };
    }

    private function renderElement(ElementNode $node, ViewContext $context): string
    {
        $attributes = $this->renderAttributes($node, $context);

        if ($node->selfClosing) {
            return "<{$node->name}{$attributes} />";
        }

        return "<{$node->name}{$attributes}>"
            . $this->renderNodes($node->children, $context)
            . "</{$node->name}>";
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
        $attributes = [];

        foreach ($node->attributes as $attribute) {
            if ($attribute instanceof AttributeSpreadNode) {
                $attributes = [...$attributes, ...$this->evaluateAttributeSpread($attribute, $context)];
                continue;
            }

            if (!$attribute instanceof AttributeNode) {
                throw new ViewRenderException("Unsupported attribute node " . $attribute::class . ".");
            }

            $value = $attribute->bound
                ? $this->evaluator->evaluate((string) $attribute->value, $context)
                : $attribute->value;

            $attributes[$attribute->name] = $value;
        }

        return $this->formatAttributes($attributes);
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

    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }
}
