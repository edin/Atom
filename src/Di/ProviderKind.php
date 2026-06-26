<?php

declare(strict_types=1);

namespace Atom\Di;

enum ProviderKind
{
    case Type;
    case Value;
    case Factory;
    case Existing;
}
