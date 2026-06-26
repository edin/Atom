<?php

declare(strict_types=1);

namespace Atom\Tests\View\PageFixtures;

use Atom\Page\Page;
use Atom\Page\PageRoute;

#[PageRoute("/first", name: "first")]
#[PageRoute("/second", name: "second")]
final class RepeatRoutePage extends Page
{
}
