<?php

declare(strict_types=1);

namespace {{ namespace }};

use Atom\Page\Page;
use Atom\Page\PageRoute;

#[PageRoute("{{ route }}")]
final class {{ class }} extends Page
{
    public string $title = "{{ title }}";

    public function get(): void
    {
    }
}
