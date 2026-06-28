<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Tests\View\PageFixtures\ArticleListPage;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PageFoundationTest extends TestCase
{
    public function testReadsPageRouteAttribute(): void
    {
        $reflection = new ReflectionClass(ArticleListPage::class);
        $route = $reflection->getAttributes(PageRoute::class)[0]->newInstance();

        $this->assertSame("/articles", $route->path);
        $this->assertSame("articles.index", $route->name);
    }

    public function testReadsPageActionAttribute(): void
    {
        $reflection = new ReflectionClass(ArticleListPage::class);
        $action = $reflection->getMethod("refresh")->getAttributes(PageAction::class)[0]->newInstance();

        $this->assertNull($action->name);
        $this->assertSame("post", $action->method);
    }
}
