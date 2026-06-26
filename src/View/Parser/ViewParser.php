<?php

declare(strict_types=1);

namespace Atom\View\Parser;

use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\FragmentNode;
use Atom\View\Ast\ForEachNode;
use Atom\View\Ast\IfBranchNode;
use Atom\View\Ast\IfNode;
use Atom\View\Ast\RawTextNode;
use Atom\View\Ast\TemplateNode;
use Atom\View\Ast\TextNode;
use Atom\View\Ast\ViewNode;
use Atom\View\Parser\Token\ViewToken;
use Atom\View\Parser\Token\ViewTokenType;

final class ViewParser
{
    /** @var ViewToken[] */
    private array $tokens = [];
    private int $position = 0;

    public function __construct(private readonly ViewTokenizer $tokenizer = new ViewTokenizer())
    {
    }

    public function parse(string $source): TemplateNode
    {
        return $this->parseTokens($this->tokenizer->tokenize($source));
    }

    /**
     * @param ViewToken[] $tokens
     */
    public function parseTokens(array $tokens): TemplateNode
    {
        $this->tokens = array_values($tokens);
        $this->position = 0;

        return new TemplateNode($this->parseChildren());
    }

    /**
     * @return ViewNode[]
     * @param string[] $untilDirectives
     */
    private function parseChildren(?string $untilTag = null, array $untilDirectives = []): array
    {
        $nodes = [];

        while (!$this->eof()) {
            $token = $this->current();

            if ($token->type === ViewTokenType::EndTag) {
                if ($untilTag === null) {
                    throw new ViewParseException("Unexpected closing tag '{$token->value}'.");
                }

                if (strcasecmp($token->value, $untilTag) !== 0) {
                    throw new ViewParseException("Expected closing tag '{$untilTag}', got '{$token->value}'.");
                }

                $this->position++;
                return $nodes;
            }

            if ($token->type === ViewTokenType::Directive && in_array(strtolower($token->value), $untilDirectives, true)) {
                return $nodes;
            }

            if ($token->type === ViewTokenType::Comment) {
                $this->position++;
                continue;
            }

            $nodes[] = $this->parseNode();
        }

        if ($untilTag !== null) {
            throw new ViewParseException("Missing closing tag for '{$untilTag}'.");
        }

        return $nodes;
    }

    private function parseNode(): ViewNode
    {
        $token = $this->current();

        return match ($token->type) {
            ViewTokenType::Text => $this->parseText($token),
            ViewTokenType::Expression => $this->parseExpression($token),
            ViewTokenType::RawText => $this->parseRawText($token),
            ViewTokenType::StartTag => $this->parseElement($token),
            ViewTokenType::Directive => $this->parseDirective($token),
            default => throw new ViewParseException("Unexpected token {$token->type->name}."),
        };
    }

    private function parseText(ViewToken $token): TextNode
    {
        $this->position++;
        return new TextNode($token->value);
    }

    private function parseExpression(ViewToken $token): ExpressionNode
    {
        $this->position++;
        return new ExpressionNode($token->value);
    }

    private function parseRawText(ViewToken $token): RawTextNode
    {
        $this->position++;
        return new RawTextNode($token->value);
    }

    private function parseElement(ViewToken $token): ElementNode
    {
        $this->position++;

        if ($token->selfClosing) {
            return new ElementNode($token->value, $token->attributes, selfClosing: true);
        }

        $children = $this->parseChildren($token->value);

        return new ElementNode(
            $token->value,
            $token->attributes,
            $this->parseFragments($token->value, $children)
        );
    }

    /**
     * @param ViewNode[] $children
     * @return ViewNode[]
     */
    private function parseFragments(string $owner, array $children): array
    {
        return array_map(
            fn(ViewNode $child): ViewNode => $child instanceof ElementNode && $this->isFragmentElement($owner, $child)
                ? $this->toFragment($owner, $child)
                : $child,
            $children
        );
    }

    private function isFragmentElement(string $owner, ElementNode $node): bool
    {
        return str_starts_with($node->name, $owner . ".")
            && strlen($node->name) > strlen($owner) + 1;
    }

    private function toFragment(string $owner, ElementNode $node): FragmentNode
    {
        return new FragmentNode(
            $owner,
            substr($node->name, strlen($owner) + 1),
            $node->attributes,
            $node->children,
            $node->selfClosing
        );
    }

    private function parseDirective(ViewToken $token): ViewNode
    {
        return match (strtolower($token->value)) {
            "if" => $this->parseIf($token),
            "foreach" => $this->parseForEach($token),
            "elseif", "else", "endif", "endforeach" => throw new ViewParseException("Unexpected directive '@{$token->value}'."),
            default => throw new ViewParseException("Unknown directive '@{$token->value}'."),
        };
    }

    private function parseIf(ViewToken $token): IfNode
    {
        if ($token->argument === null || $token->argument === "") {
            throw new ViewParseException("Directive '@if' requires a condition.");
        }

        $branches = [];
        $this->position++;
        $branches[] = new IfBranchNode(
            $token->argument,
            $this->parseChildren(untilDirectives: ["elseif", "else", "endif"])
        );
        $else = [];

        while (!$this->eof() && $this->isCurrentDirective("elseif")) {
            $elseif = $this->current();
            if ($elseif->argument === null || $elseif->argument === "") {
                throw new ViewParseException("Directive '@elseif' requires a condition.");
            }

            $this->position++;
            $branches[] = new IfBranchNode(
                $elseif->argument,
                $this->parseChildren(untilDirectives: ["elseif", "else", "endif"])
            );
        }

        if (!$this->eof() && $this->isCurrentDirective("else")) {
            $this->position++;
            $else = $this->parseChildren(untilDirectives: ["endif"]);
        }

        if ($this->eof() || !$this->isCurrentDirective("endif")) {
            throw new ViewParseException("Missing '@endif' for '@if'.");
        }

        $this->position++;

        return new IfNode($branches, $else);
    }

    private function parseForEach(ViewToken $token): ForEachNode
    {
        if ($token->argument === null || $token->argument === "") {
            throw new ViewParseException("Directive '@foreach' requires an expression.");
        }

        [$source, $key, $value] = $this->parseForEachArgument($token->argument);

        $this->position++;
        $children = $this->parseChildren(untilDirectives: ["endforeach"]);

        if ($this->eof() || !$this->isCurrentDirective("endforeach")) {
            throw new ViewParseException("Missing '@endforeach' for '@foreach'.");
        }

        $this->position++;

        return new ForEachNode($source, $value, $key, $children);
    }

    /**
     * @return array{0: string, 1: ?string, 2: string}
     */
    private function parseForEachArgument(string $argument): array
    {
        $parts = preg_split('/\s+as\s+/i', $argument, 2);
        if ($parts === false || count($parts) !== 2) {
            throw new ViewParseException("Directive '@foreach' must use 'source as value'.");
        }

        $source = trim($parts[0]);
        $target = trim($parts[1]);

        if ($source === "" || $target === "") {
            throw new ViewParseException("Directive '@foreach' must use 'source as value'.");
        }

        if (str_contains($target, "=>")) {
            [$key, $value] = array_map("trim", explode("=>", $target, 2));

            if (!$this->isVariableName($key) || !$this->isVariableName($value)) {
                throw new ViewParseException("Directive '@foreach' key and value must be variable names.");
            }

            return [$source, $key, $value];
        }

        if (!$this->isVariableName($target)) {
            throw new ViewParseException("Directive '@foreach' value must be a variable name.");
        }

        return [$source, null, $target];
    }

    private function isVariableName(string $value): bool
    {
        return preg_match('/^\$[A-Za-z_][A-Za-z0-9_]*$/', $value) === 1;
    }

    private function isCurrentDirective(string $name): bool
    {
        if ($this->eof()) {
            return false;
        }

        $token = $this->current();

        return $token->type === ViewTokenType::Directive
            && strcasecmp($token->value, $name) === 0;
    }

    private function current(): ViewToken
    {
        return $this->tokens[$this->position] ?? throw new ViewParseException("Unexpected end of template.");
    }

    private function eof(): bool
    {
        return $this->position >= count($this->tokens);
    }
}
