<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Tests\View\PageFixtures\ArticleListPage;
use Atom\Page\PageAction;
use Atom\Page\PageRegistry;
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
        $this->assertSame("Articles", $route->title);
        $this->assertSame("Browse and refresh articles.", $route->description);
    }

    public function testReadsPageActionAttribute(): void
    {
        $reflection = new ReflectionClass(ArticleListPage::class);
        $action = $reflection->getMethod("refresh")->getAttributes(PageAction::class)[0]->newInstance();

        $this->assertNull($action->name);
        $this->assertSame("post", $action->method);
    }

    public function testPageRegistryStoresCommonDirectoryMiddleware(): void
    {
        $registry = new PageRegistry();
        $registry->directory("@app/Pages", "/admin", [\Atom\Security\CsrfMiddleware::class]);

        $directory = $registry->directories()[0];

        $this->assertSame("@app/Pages", $directory->directory);
        $this->assertSame("/admin", $directory->pathPrefix);
        $this->assertSame([\Atom\Security\CsrfMiddleware::class], $directory->middlewares);
    }
}
