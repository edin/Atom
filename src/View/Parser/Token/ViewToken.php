<?php

declare(strict_types=1);

namespace Atom\View\Parser\Token;

use Atom\View\Ast\AttributeNode;
use Atom\View\Ast\AttributeSpreadNode;

final readonly class ViewToken
{
    /**
     * @param array<int, AttributeNode|AttributeSpreadNode> $attributes
     */
    public function __construct(
        public ViewTokenType $type,
        public string $value = "",
        public array $attributes = [],
        public bool $selfClosing = false,
        public int $position = 0,
        public ?string $argument = null
    ) {
    }

    public static function text(string $text, int $position): self
    {
        return new self(ViewTokenType::Text, $text, position: $position);
    }

    public static function rawText(string $text, int $position): self
    {
        return new self(ViewTokenType::RawText, $text, position: $position);
    }

    public static function expression(string $expression, int $position): self
    {
        return new self(ViewTokenType::Expression, $expression, position: $position);
    }

    /**
     * @param array<int, AttributeNode|AttributeSpreadNode> $attributes
     */
    public static function startTag(string $name, array $attributes, bool $selfClosing, int $position): self
    {
        return new self(ViewTokenType::StartTag, $name, $attributes, $selfClosing, $position);
    }

    public static function endTag(string $name, int $position): self
    {
        return new self(ViewTokenType::EndTag, $name, position: $position);
    }

    public static function directive(string $name, ?string $argument, int $position): self
    {
        return new self(ViewTokenType::Directive, $name, position: $position, argument: $argument);
    }

    public static function comment(string $text, int $position): self
    {
        return new self(ViewTokenType::Comment, $text, position: $position);
    }
}
