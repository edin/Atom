<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Tests\View\PageFixtures\ArticleListPage;
use Atom\Tests\View\PageFixtures\RepeatRoutePage;
use Atom\Page\PageDiscovery;
use Atom\Page\PageDiscoveryException;
use PHPUnit\Framework\TestCase;

final class PageDiscoveryTest extends TestCase
{
    public function testDiscoversPageDescriptorsFromDirectory(): void
    {
        $descriptors = (new PageDiscovery())->discover(__DIR__ . "/PageFixtures");

        $byPath = [];
        foreach ($descriptors as $descriptor) {
            $byPath[$descriptor->path] = $descriptor;
        }

        $this->assertArrayHasKey("/articles", $byPath);
        $this->assertSame(ArticleListPage::class, $byPath["/articles"]->pageClass);
        $this->assertSame("articles.index", $byPath["/articles"]->name);
        $this->assertSame("Articles", $byPath["/articles"]->title);
        $this->assertSame("Browse and refresh articles.", $byPath["/articles"]->description);
        $this->assertArrayHasKey("/first", $byPath);
        $this->assertSame(RepeatRoutePage::class, $byPath["/first"]->pageClass);
        $this->assertSame("first", $byPath["/first"]->name);
        $this->assertArrayHasKey("/second", $byPath);
        $this->assertSame("second", $byPath["/second"]->name);
    }

    public function testThrowsForMissingDirectory(): void
    {
        $this->expectException(PageDiscoveryException::class);

        (new PageDiscovery())->discover(__DIR__ . "/MissingPages");
    }
}
