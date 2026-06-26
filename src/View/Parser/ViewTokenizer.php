<?php

declare(strict_types=1);

namespace Atom\View\Parser;

use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\AttributeSpreadNode;
use Atom\View\Parser\Token\ViewToken;

final class ViewTokenizer
{
    private string $source = "";
    private int $position = 0;
    private int $length = 0;

    /**
     * @return ViewToken[]
     */
    public function tokenize(string $source): array
    {
        $this->source = $source;
        $this->position = 0;
        $this->length = strlen($source);
        $tokens = [];

        while (!$this->eof()) {
            if ($this->startsWith("<!--")) {
                $tokens[] = $this->readComment();
                continue;
            }

            if ($this->startsWith("</")) {
                $tokens[] = $this->readEndTag();
                continue;
            }

            if ($this->peek() === "<") {
                $token = $this->readStartTag();
                $tokens[] = $token;

                if (!$token->selfClosing && $this->isRawTextElement($token->value)) {
                    $tokens[] = $this->readRawText($token->value);
                    $tokens[] = $this->readEndTag();
                }

                continue;
            }

            if ($this->startsWith("{{")) {
                $tokens[] = $this->readExpression();
                continue;
            }

            if ($this->startsDirective()) {
                $tokens[] = $this->readDirective();
                continue;
            }

            $tokens[] = $this->readText();
        }

        return $tokens;
    }

    private function readStartTag(): ViewToken
    {
        $position = $this->position;
        $this->expect("<");
        $name = $this->readName();
        $attributes = [];

        while (!$this->eof()) {
            $this->skipWhitespace();

            if ($this->startsWith("/>")) {
                $this->position += 2;
                return ViewToken::startTag($name, $attributes, true, $position);
            }

            if ($this->startsWith(">")) {
                $this->position++;
                return ViewToken::startTag($name, $attributes, false, $position);
            }

            $attributes[] = $this->startsWith("{{")
                ? $this->readAttributeSpread()
                : $this->readAttribute();
        }

        throw new ViewParseException("Unclosed opening tag '{$name}'.");
    }

    private function readEndTag(): ViewToken
    {
        $position = $this->position;
        $this->expect("</");
        $name = $this->readName();
        $this->skipWhitespace();
        $this->expect(">");

        return ViewToken::endTag($name, $position);
    }

    private function readAttribute(): AttributeNode
    {
        $name = $this->readName();
        $bound = str_starts_with($name, ":");
        if ($bound) {
            $name = substr($name, 1);

            if ($name === "") {
                throw new ViewParseException("Bound attribute name cannot be empty.");
            }
        }

        $this->skipWhitespace();

        if (!$this->startsWith("=")) {
            if ($bound) {
                throw new ViewParseException("Bound attribute '{$name}' requires a value.");
            }

            return new AttributeNode($name);
        }

        $this->position++;
        $this->skipWhitespace();

        $value = $this->readAttributeValue();
        if ($bound && trim($value) === "") {
            throw new ViewParseException("Bound attribute '{$name}' requires an expression.");
        }

        return new AttributeNode($name, $value, $bound);
    }

    private function readAttributeSpread(): AttributeSpreadNode
    {
        return new AttributeSpreadNode($this->readExpressionValue());
    }

    private function readText(): ViewToken
    {
        $position = $this->position;
        $start = $this->position;

        while (!$this->eof() && $this->peek() !== "<" && !$this->startsWith("{{") && !$this->startsDirective()) {
            $this->position++;
        }

        return ViewToken::text(substr($this->source, $start, $this->position - $start), $position);
    }

    private function readExpression(): ViewToken
    {
        $position = $this->position;
        return ViewToken::expression($this->readExpressionValue(), $position);
    }

    private function readExpressionValue(): string
    {
        $this->expect("{{");
        $end = strpos($this->source, "}}", $this->position);

        if ($end === false) {
            throw new ViewParseException("Unclosed expression.");
        }

        $expression = trim(substr($this->source, $this->position, $end - $this->position));
        $this->position = $end + 2;

        if ($expression === "") {
            throw new ViewParseException("Expression cannot be empty.");
        }

        return $expression;
    }

    private function readDirective(): ViewToken
    {
        $position = $this->position;
        $this->expect("@");
        $name = $this->readName();
        $afterName = $this->position;

        $this->skipWhitespace();
        if (!$this->startsWith("(")) {
            $this->position = $afterName;
            return ViewToken::directive($name, null, $position);
        }

        return ViewToken::directive($name, $this->readDirectiveArgument(), $position);
    }

    private function readDirectiveArgument(): string
    {
        $this->expect("(");
        $start = $this->position;
        $depth = 1;
        $quote = null;
        $escaped = false;

        while (!$this->eof()) {
            $char = $this->peek();

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === "\\") {
                    $escaped = true;
                } elseif ($char === $quote) {
                    $quote = null;
                }

                $this->position++;
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                $this->position++;
                continue;
            }

            if ($char === "(") {
                $depth++;
                $this->position++;
                continue;
            }

            if ($char === ")") {
                $depth--;

                if ($depth === 0) {
                    $argument = substr($this->source, $start, $this->position - $start);
                    $this->position++;
                    return trim($argument);
                }
            }

            $this->position++;
        }

        throw new ViewParseException("Unclosed directive argument.");
    }

    private function readRawText(string $tag): ViewToken
    {
        $position = $this->position;
        $closing = "</{$tag}";
        $closingPosition = stripos($this->source, $closing, $this->position);

        if ($closingPosition === false) {
            throw new ViewParseException("Missing closing tag for '{$tag}'.");
        }

        $text = substr($this->source, $this->position, $closingPosition - $this->position);
        $this->position = $closingPosition;

        return ViewToken::rawText($text, $position);
    }

    private function readComment(): ViewToken
    {
        $position = $this->position;
        $end = strpos($this->source, "-->", $this->position + 4);

        if ($end === false) {
            $text = substr($this->source, $this->position + 4);
            $this->position = $this->length;
            return ViewToken::comment($text, $position);
        }

        $text = substr($this->source, $this->position + 4, $end - ($this->position + 4));
        $this->position = $end + 3;

        return ViewToken::comment($text, $position);
    }

    private function readName(): string
    {
        $start = $this->position;

        while (!$this->eof() && preg_match('/[A-Za-z0-9_:\.-]/', $this->peek()) === 1) {
            $this->position++;
        }

        if ($start === $this->position) {
            throw new ViewParseException("Expected name at position {$this->position}.");
        }

        return substr($this->source, $start, $this->position - $start);
    }

    private function readAttributeValue(): string
    {
        $quote = $this->peek();
        if ($quote === '"' || $quote === "'") {
            $this->position++;
            $start = $this->position;
            $end = strpos($this->source, $quote, $this->position);

            if ($end === false) {
                throw new ViewParseException("Unclosed attribute value.");
            }

            $this->position = $end + 1;
            return substr($this->source, $start, $end - $start);
        }

        $start = $this->position;
        while (!$this->eof() && !ctype_space($this->peek()) && !$this->startsWith(">") && !$this->startsWith("/>")) {
            $this->position++;
        }

        return substr($this->source, $start, $this->position - $start);
    }

    private function isRawTextElement(string $name): bool
    {
        return in_array(strtolower($name), ["script", "style"], true);
    }

    private function skipWhitespace(): void
    {
        while (!$this->eof() && ctype_space($this->peek())) {
            $this->position++;
        }
    }

    private function expect(string $text): void
    {
        if (!$this->startsWith($text)) {
            throw new ViewParseException("Expected '{$text}' at position {$this->position}.");
        }

        $this->position += strlen($text);
    }

    private function startsWith(string $text): bool
    {
        return substr($this->source, $this->position, strlen($text)) === $text;
    }

    private function startsDirective(): bool
    {
        $previous = $this->position > 0 ? $this->source[$this->position - 1] : "";

        return $this->peek() === "@"
            && ($previous === "" || ctype_space($previous) || str_contains(">([{", $previous))
            && preg_match('/[A-Za-z_]/', $this->source[$this->position + 1] ?? "") === 1;
    }

    private function peek(): string
    {
        return $this->source[$this->position] ?? "";
    }

    private function eof(): bool
    {
        return $this->position >= $this->length;
    }
}
