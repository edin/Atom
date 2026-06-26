<?php

declare(strict_types=1);

namespace Atom\Di;

enum ProviderLifetime
{
    case Transient;
    case Scoped;
    case Singleton;
}
