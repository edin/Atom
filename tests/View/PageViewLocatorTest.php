<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Tests\View\PageFixtures\ArticleListPage;
use Atom\Tests\View\PageFixtures\CustomTemplatePage;
use Atom\Tests\View\PageFixtures\MissingTemplatePage;
use Atom\Page\PageViewLocator;
use Atom\Page\PageViewLocatorException;
use PHPUnit\Framework\TestCase;

final class PageViewLocatorTest extends TestCase
{
    public function testLocatesSameFolderAtomHtmlTemplateByConvention(): void
    {
        $path = (new PageViewLocator())->locate(ArticleListPage::class);

        $this->assertStringEndsWith("ArticleListPage.atom.html", $path);
        $this->assertFileExists($path);
    }

    public function testLocatesCustomRelativeTemplate(): void
    {
        $path = (new PageViewLocator())->locate(CustomTemplatePage::class);

        $this->assertStringEndsWith("CustomView.atom.html", $path);
        $this->assertFileExists($path);
    }

    public function testThrowsWhenTemplateIsMissing(): void
    {
        $this->expectException(PageViewLocatorException::class);

        (new PageViewLocator())->locate(MissingTemplatePage::class);
    }
}
