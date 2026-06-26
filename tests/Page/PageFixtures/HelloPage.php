<?php

declare(strict_types=1);

namespace Atom\Tests\Page\PageFixtures;

use Atom\Page\Page;
use Atom\Page\PageRoute;

#[PageRoute("/hello-page", name: "hello.page")]
final class HelloPage extends Page
{
}
