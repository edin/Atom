<?php

declare(strict_types=1);

namespace Atom\Tests\View\PageFixtures;

use Atom\Page\Page;

final class CustomTemplatePage extends Page
{
    public function template(): ?string
    {
        return "CustomView.atom.html";
    }
}
