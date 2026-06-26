<?php

declare(strict_types=1);

namespace Atom\Tests\Page\DefaultRegistration;

use Atom\Page\Page;

final readonly class DefaultPageRegistration
{
    public static function register(): array
    {
        return Page::registerPages();
    }
}
