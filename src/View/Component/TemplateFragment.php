<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\AttributeSpreadNode;
use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\ForEachNode;
use Atom\View\Ast\FragmentNode;
use Atom\View\Ast\IfBranchNode;
use Atom\View\Ast\IfNode;
use Atom\View\Ast\RawTextNode;
use Atom\View\Ast\TextNode;
use Atom\View\Ast\ViewNode;
use Atom\View\Html;
use Closure;

final readonly class TemplateFragment
{
    /**
     * @param ViewNode[] $nodes
     * @param Closure(array<string, mixed>): string $renderer
     */
    public function __construct(
        private array $nodes,
        private Closure $renderer,
        private ?string $source = null
    ) {
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function render(array $variables = []): string
    {
        return ($this->renderer)($variables);
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function renderOr(string $fallback, array $variables = []): string
    {
        $content = $this->render($variables);

        return trim($content) === "" ? $fallback : $content;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function isEmpty(array $variables = []): bool
    {
        return trim($this->render($variables)) === "";
    }

    /**
     * @return ViewNode[]
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    public function fragment(): Fragment
    {
        return new Fragment($this->renderer);
    }

    public function source(): string
    {
        return $this->source ?? $this->sourceFromNodes($this->nodes);
    }

    /**
     * @param ViewNode[] $nodes
     */
    private function sourceFromNodes(array $nodes): string
    {
        return implode("", array_map($this->sourceFromNode(...), $nodes));
    }

    private function sourceFromNode(ViewNode $node): string
    {
        return match (true) {
            $node instanceof TextNode => $node->text,
            $node instanceof RawTextNode => $node->text,
            $node instanceof ExpressionNode => "{{ " . $node->expression . " }}",
            $node instanceof ElementNode => $this->elementSource($node),
            $node instanceof FragmentNode => $this->fragmentSource($node),
            $node instanceof IfNode => $this->ifSource($node),
            $node instanceof ForEachNode => $this->forEachSource($node),
            default => "",
        };
    }

    private function elementSource(ElementNode $node): string
    {
        $attributes = $this->attributesSource($node->attributes);
        if ($node->selfClosing) {
            return "<{$node->name}{$attributes} />";
        }

        return "<{$node->name}{$attributes}>" . $this->sourceFromNodes($node->children) . "</{$node->name}>";
    }

    private function fragmentSource(FragmentNode $node): string
    {
        $attributes = $this->attributesSource($node->attributes);
        if ($node->selfClosing) {
            return "<{$node->owner}.{$node->name}{$attributes} />";
        }

        return "<{$node->owner}.{$node->name}{$attributes}>" . $this->sourceFromNodes($node->children) . "</{$node->owner}.{$node->name}>";
    }

    /**
     * @param array<int, AttributeNode|AttributeSpreadNode> $attributes
     */
    private function attributesSource(array $attributes): string
    {
        $source = "";

        foreach ($attributes as $attribute) {
            if ($attribute instanceof AttributeSpreadNode) {
                $source .= " {{ " . $attribute->expression . " }}";
                continue;
            }

            if (!$attribute instanceof AttributeNode) {
                continue;
            }

            if ($attribute->value === true) {
                $source .= " " . $attribute->name;
                continue;
            }

            if ($attribute->value instanceof ExpressionNode) {
                $source .= " :" . $attribute->name . '="' . $attribute->value->expression . '"';
                continue;
            }

            $source .= " " . $attribute->name . '="' . Html::escape($attribute->value) . '"';
        }

        return $source;
    }

    private function ifSource(IfNode $node): string
    {
        $source = "";

        foreach ($node->branches as $index => $branch) {
            $source .= $this->ifBranchSource($branch, $index === 0);
        }

        if ($node->else !== []) {
            $source .= "@else" . $this->sourceFromNodes($node->else);
        }

        return $source . "@endif";
    }

    private function ifBranchSource(IfBranchNode $branch, bool $first): string
    {
        return ($first ? "@if" : "@elseif") . "(" . $branch->condition . ")" . $this->sourceFromNodes($branch->children);
    }

    private function forEachSource(ForEachNode $node): string
    {
        $key = $node->key === null ? "" : $node->key . " => ";

        return "@foreach(" . $node->source . " as " . $key . $node->value . ")"
            . $this->sourceFromNodes($node->children)
            . "@endforeach";
    }
}
