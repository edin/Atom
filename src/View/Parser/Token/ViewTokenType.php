<?php

declare(strict_types=1);

namespace Atom\View\Parser\Token;

enum ViewTokenType
{
    case Text;
    case Expression;
    case RawText;
    case StartTag;
    case EndTag;
    case Directive;
    case Comment;
}
