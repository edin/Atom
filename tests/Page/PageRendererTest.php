<?php

declare(strict_types=1);

namespace Atom\Tests\Page;

use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Http\Request;
use Atom\Page\Page;
use Atom\Page\PageAction;
use Atom\Page\PageActionHandler;
use Atom\Page\PageRenderer;
use Atom\Page\PageRoute;
use Atom\Page\PageRouteHandler;
use Atom\Page\PageRouteMetadata;
use Atom\Page\PageRouteRegistrar;
use Atom\Page\State;
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

    public function testRendererOnlyInvokesGetBeforeRenderingTemplate(): void
    {
        $context = new InjectionContext();
        $context->set(Request::class, new Request("POST", "/"));
        $renderer = new PageRenderer(new Injector(), $context);

        $html = $renderer->render(PostRenderedTestPage::class);

        $this->assertSame("<h1>Loaded</h1>\n", $html);
    }

    public function testPageActionHandlerInvokesAttributedActionAndRerendersPage(): void
    {
        $router = new Router();
        (new PageRouteRegistrar())->register($router, [
            new \Atom\Page\PageDescriptor("/post-page", PostRenderedTestPage::class),
        ]);
        $match = (new RouteMatcher($router))->match("POST", "/post-page");

        $context = new InjectionContext();
        $context->set(Request::class, new Request("POST", "/post-page", [], ["_action" => "save"]));
        $context->set(\Atom\Router\MatchedRoute::class, $match->matchedRoute);
        $injector = new Injector();
        $handler = new PageActionHandler($injector, $context);
        $renderer = new PageRenderer($injector, $context);

        $html = $handler->handle($renderer, $match->matchedRoute, $context->get(Request::class));

        $this->assertTrue($match->isFound());
        $this->assertSame(PageActionHandler::class, $match->matchedRoute->getRouteAction()->controllerType);
        $this->assertSame("<h1>Posted</h1>\n", $html);
    }

    public function testPageActionHandlerParsesActionArguments(): void
    {
        $router = new Router();
        (new PageRouteRegistrar())->register($router, [
            new \Atom\Page\PageDescriptor("/delete-page", DeleteRenderedTestPage::class),
        ]);
        $match = (new RouteMatcher($router))->match("POST", "/delete-page");

        $context = new InjectionContext();
        $context->set(Request::class, new Request("POST", "/delete-page", [], ["_action" => 'delete(12, "hard")']));
        $context->set(\Atom\Router\MatchedRoute::class, $match->matchedRoute);
        $injector = new Injector();
        $handler = new PageActionHandler($injector, $context);
        $renderer = new PageRenderer($injector, $context);

        $html = $handler->handle($renderer, $match->matchedRoute, $context->get(Request::class));

        $this->assertSame("<h1>Deleted 12 hard</h1>\n", $html);
    }

    public function testPageStateIsRestoredBeforeActionAndCapturedAfterRender(): void
    {
        $serializer = new \Atom\Page\JsonPageStateSerializer();
        $page = new CounterRenderedTestPage();
        $page->count = 2;
        $state = $serializer->serialize($page);

        $router = new Router();
        (new PageRouteRegistrar())->register($router, [
            new \Atom\Page\PageDescriptor("/counter-page", CounterRenderedTestPage::class),
        ]);
        $match = (new RouteMatcher($router))->match("POST", "/counter-page");

        $context = new InjectionContext();
        $context->set(Request::class, new Request("POST", "/counter-page", [], [
            "_action" => "increment",
            "_state" => $state,
        ]));
        $context->set(\Atom\Router\MatchedRoute::class, $match->matchedRoute);
        $injector = new Injector();
        $handler = new PageActionHandler($injector, $context);
        $renderer = new PageRenderer($injector, $context);

        $html = $handler->handle($renderer, $match->matchedRoute, $context->get(Request::class));

        $this->assertStringContainsString("<h1>3</h1>", $html);
        $this->assertStringContainsString('name="atom-state"', $html);
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

    public function testRegistersDiscoveredPageRouteWithPrefix(): void
    {
        $router = new Router();
        $entries = (new PageRouteRegistrar())->registerDirectory($router, __DIR__ . "/PageFixtures", "/module");

        $match = (new RouteMatcher($router))->match("GET", "/module/hello-page");

        $this->assertCount(1, $entries);
        $this->assertTrue($match->isFound());
        $this->assertSame("/module/hello-page", $entries[0]->getFullPath());
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

    public function get(): void
    {
        $this->title = "Loaded";
    }

    #[PageAction("save")]
    public function save(): void
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

final class DeleteRenderedTestPage extends Page
{
    public string $title = "Before";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("delete")]
    public function delete(int $id, string $mode): void
    {
        $this->title = "Deleted {$id} {$mode}";
    }
}

final class CounterRenderedTestPage extends Page
{
    #[State]
    public int $count = 0;

    public string $title = "0";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("increment")]
    public function increment(): void
    {
        $this->count++;
        $this->title = (string) $this->count;
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
