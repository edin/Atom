<?php

declare(strict_types=1);

namespace Atom\Tests\Page;

use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Http\Request;
use Atom\Page\Page;
use Atom\Page\PageRenderer;
use Atom\Page\PageRoute;
use Atom\Page\PageRouteHandler;
use Atom\Page\PageRouteMetadata;
use Atom\Page\PageRouteRegistrar;
use Atom\Router\Route;
use Atom\Router\RouteMatcher;
use Atom\Router\Router;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/DefaultRegistration/DefaultPageRegistration.php";

final class PageRendererTest extends TestCase
{
    public function testRendersPageTemplateWithPageAsThisAndPageVariable(): void
    {
        $renderer = new PageRenderer(new Injector(), new InjectionContext());

        $html = $renderer->render(RenderedTestPage::class);

        $this->assertSame("<h1>Hello Page</h1><p>Hello Page</p>\n", $html);
    }

    public function testInvokesGetBeforeRenderingTemplate(): void
    {
        $renderer = new PageRenderer(new Injector(), new InjectionContext());

        $html = $renderer->render(GetRenderedTestPage::class);

        $this->assertSame("<h1>Loaded</h1>\n", $html);
    }

    public function testInvokesMethodMatchingRequestMethodBeforeRenderingTemplate(): void
    {
        $context = new InjectionContext();
        $context->set(Request::class, new Request("POST", "/"));
        $renderer = new PageRenderer(new Injector(), $context);

        $html = $renderer->render(PostRenderedTestPage::class);

        $this->assertSame("<h1>Posted</h1>\n", $html);
    }

    public function testRendersPageInsideConfiguredLayoutComponent(): void
    {
        $renderer = new PageRenderer(new Injector(), new InjectionContext());

        $html = $renderer->render(LayoutRenderedTestPage::class);

        $this->assertSame("<layout title=\"Body\"><h1>Body</h1>\n</layout>", $html);
    }

    public function testRegistersDiscoveredPageRoute(): void
    {
        $router = new Router();
        $entries = (new PageRouteRegistrar())->registerDirectory($router, __DIR__ . "/PageFixtures");

        $match = (new RouteMatcher($router))->match("GET", "/hello-page");

        $this->assertCount(1, $entries);
        $this->assertTrue($match->isFound());
        $this->assertSame("hello.page", $match->matchedRoute->getRouteEntry()->getName());
        $this->assertSame(PageRouteHandler::class, $match->matchedRoute->getRouteAction()->controllerType);
        $this->assertSame("render", $match->matchedRoute->getRouteAction()->methodName);
        $this->assertSame(
            PageFixtures\HelloPage::class,
            $match->matchedRoute->getRouteEntry()->getMetadataOfType(PageRouteMetadata::class)?->pageClass
        );
    }

    public function testPageRegistersDiscoveredRoutesOnSharedRouter(): void
    {
        $router = new Router();
        Route::setRouter($router);

        try {
            $entries = Page::registerPages(__DIR__ . "/PageFixtures");
        } finally {
            Route::clearRouter();
        }

        $match = (new RouteMatcher($router))->match("GET", "/hello-page");

        $this->assertCount(1, $entries);
        $this->assertTrue($match->isFound());
    }

    public function testPageRegistersPagesFromCallerPagesDirectoryByDefault(): void
    {
        $router = new Router();
        Route::setRouter($router);

        try {
            $entries = DefaultRegistration\DefaultPageRegistration::register();
        } finally {
            Route::clearRouter();
        }

        $match = (new RouteMatcher($router))->match("GET", "/default-page");

        $this->assertCount(1, $entries);
        $this->assertTrue($match->isFound());
    }
}

final class RenderedTestPage extends Page
{
    public string $title = "Hello Page";

    public function template(): ?string
    {
        return "RenderedTestPage.atom.html";
    }
}

final class GetRenderedTestPage extends Page
{
    public string $title = "Before";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    public function get(): void
    {
        $this->title = "Loaded";
    }
}

final class PostRenderedTestPage extends Page
{
    public string $title = "Before";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    public function post(): void
    {
        $this->title = "Posted";
    }
}

final class LayoutRenderedTestPage extends Page
{
    public ?string $layout = TestLayoutComponent::class;

    public string $title = "Before";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    public function get(): void
    {
        $this->title = "Body";
    }

}

final class TestLayoutComponent implements ComponentInterface
{
    public Page $page;

    public ?Fragment $content = null;

    public function render(): string
    {
        return '<layout title="' . $this->page->title . '">' . $this->content?->render() . '</layout>';
    }
}
