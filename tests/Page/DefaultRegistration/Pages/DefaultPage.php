<?php

declare(strict_types=1);

namespace Atom\Tests\Page\DefaultRegistration\Pages;

use Atom\Page\Page;
use Atom\Page\PageRoute;

#[PageRoute("/default-page", name: "default.page")]
final class DefaultPage extends Page
{
}
