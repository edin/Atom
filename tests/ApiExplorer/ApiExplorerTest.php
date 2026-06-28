<?php

declare(strict_types=1);

namespace Atom\Tests\ApiExplorer;

use Atom\ApiExplorer\ApiExplorer;
use Atom\ApiExplorer\ApiExplorerHandler;
use Atom\ApiExplorer\ApiExplorerRouteMetadata;
use Atom\ApiExplorer\UI\Components\AppShell;
use Atom\ApiExplorer\UI\Components\EndpointDetails;
use Atom\ApiExplorer\UI\Components\EndpointList;
use Atom\ApiExplorer\UI\Components\TryRequestPanel;
use Atom\ApiExplorer\UI\Pages\ApiExplorerPage;
use Atom\ApiExplorer\UI\Pages\ApiExplorerPreviewPage;
use Atom\Application;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Dispatcher\Dispatcher;
use Atom\Http\Response;
use Atom\Http\Request;
use Atom\Page\PageRenderer;
use Atom\Page\PageRouteMetadata;
use Atom\Router\MatchedRoute;
use Atom\Router\RouteEntry;
use Atom\Router\RouteAction;
use Atom\Router\Router;
use Atom\View\Component\ComponentRegistry;
use PHPUnit\Framework\TestCase;

final class ApiExplorerTest extends TestCase
{
    protected function setUp(): void
    {
        Application::$app = null;
    }

    protected function tearDown(): void
    {
        Application::$app = null;
    }

    public function testRegistersExplorerRoute(): void
    {
        $router = new Router();
        $entry = ApiExplorer::register($router, "/dev/api");

        $this->assertSame("/dev/api", $entry->getFullPath());
        $this->assertSame("GET", $entry->getMethod());
        $this->assertSame(ApiExplorerHandler::class, $entry->getController());
        $this->assertSame("index", $entry->getMethodName());
    }

    public function testCreatesExplorerModule(): void
    {
        $router = new Router();
        ApiExplorer::module("/dev/api")->register(new \Atom\Module\ModuleContext(
            $router,
            \Atom\Di\Injector::create(),
            new \Atom\View\Component\ComponentRegistry(),
        ));

        $entry = $this->routeByName($router, "atom.api-explorer");
        $page = $this->routeByPath($router, "/dev/api/page");
        $preview = $this->routeByPath($router, "/dev/api/preview");

        $this->assertSame("/dev/api", $entry->getFullPath());
        $this->assertSame(ApiExplorerHandler::class, $entry->getController());
        $this->assertSame("/dev/api/resources", $entry->getMetadataOfType(ApiExplorerRouteMetadata::class)->resourcePath);
        $this->assertSame("/api", $entry->getMetadataOfType(ApiExplorerRouteMetadata::class)->apiPathPrefix);
        $this->assertSame("/dev/api/preview", $preview->getFullPath());
        $this->assertSame(ApiExplorerPreviewPage::class, $preview->getMetadataOfType(PageRouteMetadata::class)?->pageClass);
        $this->assertSame("/api", $page->getMetadataOfType(ApiExplorerRouteMetadata::class)?->apiPathPrefix);
        $this->assertSame(ApiExplorerPage::class, $page->getMetadataOfType(PageRouteMetadata::class)?->pageClass);
    }

    public function testModulePageCanRenderModuleComponents(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $app->registerModule(ApiExplorer::module("/dev/api"));

        $html = $app->getInjector()->get(PageRenderer::class)->render(ApiExplorerPreviewPage::class);

        $this->assertSame(AppShell::class, $app->getInjector()->get(ComponentRegistry::class)->get("ApiExplorer.AppShell"));
        $this->assertSame(EndpointList::class, $app->getInjector()->get(ComponentRegistry::class)->get("ApiExplorer.EndpointList"));
        $this->assertSame(EndpointDetails::class, $app->getInjector()->get(ComponentRegistry::class)->get("ApiExplorer.EndpointDetails"));
        $this->assertSame(TryRequestPanel::class, $app->getInjector()->get(ComponentRegistry::class)->get("ApiExplorer.TryRequest"));
        $this->assertStringContainsString("<!doctype html>", $html);
        $this->assertStringContainsString('/atom/api/resources/api-explorer.css', $html);
        $this->assertStringContainsString('/atom/api/resources/api-explorer.js?v=10', $html);
        $this->assertStringContainsString('<header class="topbar">', $html);
        $this->assertStringContainsString("/api/articles/{id}", $html);
        $this->assertStringContainsString('class="request-url-field"', $html);
        $this->assertStringContainsString('<span class="request-method">GET</span>', $html);
        $this->assertStringContainsString('<th style="width: 96px">Location</th>', $html);
        $this->assertStringContainsString('<span class="schema-field">page</span>', $html);
        $this->assertStringContainsString('<span class="schema-type">int</span>', $html);
        $this->assertStringNotContainsString("<th>Model</th>", $html);
        $this->assertStringNotContainsString("<td><code>query.page</code></td>", $html);
        $this->assertStringContainsString("Response shape", $html);
        $this->assertStringContainsString("ArticleListResponse", $html);
        $this->assertStringContainsString('<span class="schema-field">items</span>', $html);
        $this->assertStringContainsString('<span class="schema-type">ArticleResponse[]</span>', $html);
        $this->assertStringContainsString('href="?id=1"', $html);
        $this->assertStringContainsString('class="selected" href="?id=0"', $html);
    }

    public function testModulePageCanSelectOperationByQueryId(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $app->registerModule(ApiExplorer::module("/dev/api"));

        $context = new InjectionContext();
        $context->set(Request::class, new Request("GET", "/dev/api/preview", ["id" => 1]));
        $html = $app->getInjector()->get(PageRenderer::class, $context)->render(ApiExplorerPreviewPage::class);

        $this->assertStringContainsString('class="selected" href="?id=1"', $html);
        $this->assertStringContainsString("<code>/api/articles</code>", $html);
        $this->assertStringContainsString('<small class="method-tag">POST</small>', $html);
        $this->assertStringContainsString('<span class="request-method">POST</span>', $html);
        $this->assertStringContainsString("Error responses", $html);
        $this->assertStringContainsString('<span class="status-code">422</span>', $html);
        $this->assertStringContainsString("ValidationErrorResponse", $html);
        $this->assertStringNotContainsString('<article id="operation-0"', $html);
    }

    public function testRealModulePageRendersRegisteredApiRoutes(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $app->getRouter()->add(
            RouteEntry::route("GET", "/api/ping", RouteAction::fromClosure(fn(): string => "pong"))
                ->name("api.ping")
                ->description("Health check endpoint.")
        );
        $app->getRouter()->add(
            RouteEntry::route("GET", "/dashboard", RouteAction::fromClosure(fn(): string => "dashboard"))
                ->name("dashboard")
        );
        $app->registerModule(ApiExplorer::module("/dev/api"));

        $html = $app->getInjector()->get(PageRenderer::class)->render(ApiExplorerPage::class);

        $this->assertStringContainsString("<!doctype html>", $html);
        $this->assertStringContainsString("/api/ping", $html);
        $this->assertStringContainsString("Health check endpoint.", $html);
        $this->assertStringContainsString("1 operation", $html);
        $this->assertStringNotContainsString("/dashboard", $html);
        $this->assertStringNotContainsString("/dev/api/page", $html);
        $this->assertStringNotContainsString("/dev/api/resources", $html);
    }

    public function testModulePageCanSubmitTryRequestWithoutJavascript(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $app->getRouter()->add(
            RouteEntry::route("POST", "/api/echo", RouteAction::fromClosure(
                fn(Request $request): array => ["name" => $request->post()->string("name")]
            ))
        );
        $app->registerModule(ApiExplorer::module("/dev/api"));

        $request = new Request("POST", "/dev/api/page", [], [
            "_action" => "try",
            "id" => "0",
            "method" => "POST",
            "url" => "/api/echo",
            "body" => '{"name":"Atom"}',
        ]);
        $context = new InjectionContext();
        $context->set(Request::class, $request);

        $html = $app->getInjector()->get(Dispatcher::class, $context)->handle($request)->getContent();

        $this->assertStringContainsString('<form class="request-form" method="post"', $html);
        $this->assertStringContainsString('atom:action="try"', $html);
        $this->assertStringContainsString('name="url"', $html);
        $this->assertStringContainsString('HTTP 200 OK', $html);
        $this->assertStringContainsString('&quot;name&quot;: &quot;Atom&quot;', $html);
    }

    public function testModulePagePreservesEditedTryRequestUrlAfterSubmit(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $app->getRouter()->add(
            RouteEntry::route("GET", "/api/echo", RouteAction::fromClosure(
                fn(Request $request): array => ["name" => $request->query()->string("name")]
            ))
        );
        $app->registerModule(ApiExplorer::module("/dev/api"));

        $request = new Request("POST", "/dev/api/page", [], [
            "_action" => "try",
            "id" => "0",
            "method" => "GET",
            "url" => "/api/echo?name=Atom",
            "body" => "{}",
        ]);
        $context = new InjectionContext();
        $context->set(Request::class, $request);

        $html = $app->getInjector()->get(Dispatcher::class, $context)->handle($request)->getContent();

        $this->assertStringContainsString('value="/api/echo?name=Atom"', $html);
        $this->assertStringContainsString('&quot;name&quot;: &quot;Atom&quot;', $html);
    }

    public function testModuleCanUseCustomApiRoutePrefix(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $app->getRouter()->add(RouteEntry::route("GET", "/internal/ping", RouteAction::fromClosure(fn(): string => "pong")));
        $app->getRouter()->add(RouteEntry::route("GET", "/api/ping", RouteAction::fromClosure(fn(): string => "pong")));
        $app->registerModule(ApiExplorer::module("/dev/api", "/internal"));

        $context = new InjectionContext();
        $context->set(MatchedRoute::class, new MatchedRoute($this->routeByPath($app->getRouter(), "/dev/api/page")));
        $html = $app->getInjector()->get(PageRenderer::class, $context)->render(ApiExplorerPage::class);

        $this->assertStringContainsString("/internal/ping", $html);
        $this->assertStringNotContainsString("/api/ping", $html);
        $this->assertStringContainsString("1 operation", $html);
    }

    public function testHandlerRendersApiModelAsHtml(): void
    {
        $router = new Router();
        $router->add(RouteEntry::route("GET", "/api/ping", RouteAction::fromClosure(fn(): string => "pong")));
        $entry = $router->add(
            RouteEntry::route("GET", "/atom/api", RouteAction::fromMethod(ApiExplorerHandler::class, "index"))
                ->metadata(new ApiExplorerRouteMetadata("/atom/api/resources"))
        );

        $response = (new ApiExplorerHandler($router))->index(new Response(), new MatchedRoute($entry));

        $this->assertSame(["text/html; charset=utf-8"], $response->headers()->all("Content-Type"));
        $this->assertStringContainsString("Atom API Explorer", $response->getContent());
        $this->assertStringContainsString('/atom/api/resources/api-explorer.css', $response->getContent());
        $this->assertStringContainsString("Try request", $response->getContent());
        $this->assertStringContainsString("/api/ping", $response->getContent());
        $this->assertStringContainsString("Closure", $response->getContent());
    }

    private function routeByName(Router $router, string $name): RouteEntry
    {
        foreach ($router->getAllRoutes() as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }

        $this->fail("Route '{$name}' was not registered.");
    }

    private function routeByPath(Router $router, string $path): RouteEntry
    {
        foreach ($router->getAllRoutes() as $route) {
            if ($route->getFullPath() === $path) {
                return $route;
            }
        }

        $this->fail("Route '{$path}' was not registered.");
    }
}

final class ApiExplorerTestApplication extends Application
{
    protected function bootstrap(Injector $injector): void
    {
    }
}
