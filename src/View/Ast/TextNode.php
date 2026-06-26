<?php

declare(strict_types=1);

namespace Atom\View\Ast;

final readonly class TextNode implements ViewNode
{
    public function __construct(public string $text)
    {
    }
}
