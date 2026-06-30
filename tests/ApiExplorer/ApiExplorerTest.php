<?php

declare(strict_types=1);

namespace Atom\Tests\ApiExplorer;

use Atom\Api\Attributes\ArrayOf;
use Atom\Api\Attributes\ErrorResponse;
use Atom\Api\Attributes\ResponseOf;
use Atom\Modules\ApiExplorer\ApiExplorer;
use Atom\Modules\ApiExplorer\ApiExplorerConfig;
use Atom\Modules\ApiExplorer\ApiExplorerRedirectHandler;
use Atom\Modules\ApiExplorer\UI\Components\AppShell;
use Atom\Modules\ApiExplorer\UI\Components\EndpointDetails;
use Atom\Modules\ApiExplorer\UI\Components\EndpointList;
use Atom\Modules\ApiExplorer\UI\Components\TryRequestPanel;
use Atom\Modules\ApiExplorer\UI\Pages\ApiExplorerPage;
use Atom\Application;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Dispatcher\Dispatcher;
use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Hydrator\Attributes\FromQuery;
use Atom\Hydrator\Attributes\FromRoute;
use Atom\Http\Response;
use Atom\Http\Request;
use Atom\Page\PageRenderer;
use Atom\Page\PageRouteMetadata;
use Atom\Router\MatchedRoute;
use Atom\Router\RouteEntry;
use Atom\Router\Router;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Required;
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
        $this->assertSame(ApiExplorerRedirectHandler::class, $entry->getRouteAction()->controllerType);
        $this->assertSame("redirect", $entry->getRouteAction()->methodName);
    }

    public function testCreatesExplorerModule(): void
    {
        $router = new Router();
        ApiExplorer::module()->register(new \Atom\Module\ModuleContext(
            $router,
            \Atom\Di\Injector::create(),
            new \Atom\View\Component\ComponentRegistry(),
            "/dev/api"
        ));

        $entry = $this->routeByName($router, "atom.api-explorer");
        $frameworkResources = $this->routeByPath($router, "/atom/framework/resources/{path*}");
        $apiResources = $this->routeByPath($router, "/dev/api/resources/{path*}");
        $page = $this->routeByPath($router, "/dev/api/explorer");

        $this->assertSame("/dev/api", $entry->getFullPath());
        $this->assertSame("/atom/framework/resources/{path*}", $frameworkResources->getFullPath());
        $this->assertSame("/dev/api/resources/{path*}", $apiResources->getFullPath());
        $this->assertSame(ApiExplorerRedirectHandler::class, $entry->getRouteAction()->controllerType);
        $this->assertSame("redirect", $entry->getRouteAction()->methodName);
        $this->assertSame("/dev/api/resources", $entry->getMetadataOfType(ApiExplorerConfig::class)->resourcePath);
        $this->assertSame("/dev/api/explorer", $entry->getMetadataOfType(ApiExplorerConfig::class)->pagePath);
        $this->assertSame("/api", $entry->getMetadataOfType(ApiExplorerConfig::class)->apiPathPrefix);
        $this->assertSame("/api", $page->getMetadataOfType(ApiExplorerConfig::class)?->apiPathPrefix);
        $this->assertSame(ApiExplorerPage::class, $page->getMetadataOfType(PageRouteMetadata::class)?->pageClass);
    }

    public function testModulePageCanRenderModuleComponents(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $this->registerDocumentedApiRoutes($app->getRouter());
        $app->registerModule(ApiExplorer::module(), "/dev/api");

        $context = new InjectionContext();
        $context->set(MatchedRoute::class, new MatchedRoute($this->routeByPath($app->getRouter(), "/dev/api/explorer")));
        $html = $app->getInjector()->get(PageRenderer::class, $context)->render(ApiExplorerPage::class);

        $this->assertSame(AppShell::class, $app->getInjector()->get(ComponentRegistry::class)->get("ApiExplorer.AppShell"));
        $this->assertSame(EndpointList::class, $app->getInjector()->get(ComponentRegistry::class)->get("ApiExplorer.EndpointList"));
        $this->assertSame(EndpointDetails::class, $app->getInjector()->get(ComponentRegistry::class)->get("ApiExplorer.EndpointDetails"));
        $this->assertSame(TryRequestPanel::class, $app->getInjector()->get(ComponentRegistry::class)->get("ApiExplorer.TryRequest"));
        $this->assertStringContainsString("<!doctype html>", $html);
        $this->assertStringContainsString('/dev/api/resources/api-explorer.css', $html);
        $this->assertStringContainsString('/atom/framework/resources/atom.js?v=2', $html);
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
        $this->assertStringContainsString("ApiExplorerPageResponse", $html);
        $this->assertStringContainsString('<span class="schema-field">items</span>', $html);
        $this->assertStringContainsString('<span class="schema-type">ApiExplorerArticleResponse[]</span>', $html);
        $this->assertStringContainsString('href="?id=1"', $html);
        $this->assertStringContainsString('class="selected" href="?id=0"', $html);
    }

    public function testModulePageCanSelectOperationByQueryId(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $this->registerDocumentedApiRoutes($app->getRouter());
        $app->registerModule(ApiExplorer::module(), "/dev/api");

        $context = new InjectionContext();
        $context->set(Request::class, new Request("GET", "/dev/api/explorer", ["id" => 1]));
        $html = $app->getInjector()->get(PageRenderer::class, $context)->render(ApiExplorerPage::class);

        $this->assertStringContainsString('class="selected" href="?id=1"', $html);
        $this->assertStringContainsString("<code>/api/articles</code>", $html);
        $this->assertStringContainsString('<small class="method-tag">POST</small>', $html);
        $this->assertStringContainsString('<span class="request-method">POST</span>', $html);
        $this->assertStringContainsString("Error responses", $html);
        $this->assertStringContainsString('<span class="status-code">422</span>', $html);
        $this->assertStringContainsString("ApiExplorerValidationErrorResponse", $html);
        $this->assertStringNotContainsString('<article id="operation-0"', $html);
    }

    public function testRealModulePageRendersRegisteredApiRoutes(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $app->getRouter()->add(
            RouteEntry::get("/api/ping", fn(): string => "pong")
                ->name("api.ping")
                ->description("Health check endpoint.")
        );
        $app->getRouter()->add(
            RouteEntry::get("/dashboard", fn(): string => "dashboard")
                ->name("dashboard")
        );
        $app->registerModule(ApiExplorer::module(), "/dev/api");

        $html = $app->getInjector()->get(PageRenderer::class)->render(ApiExplorerPage::class);

        $this->assertStringContainsString("<!doctype html>", $html);
        $this->assertStringContainsString("/api/ping", $html);
        $this->assertStringContainsString("Health check endpoint.", $html);
        $this->assertStringContainsString("1 operation", $html);
        $this->assertStringNotContainsString("/dashboard", $html);
        $this->assertStringNotContainsString("/dev/api/explorer", $html);
        $this->assertStringNotContainsString("/dev/api/resources", $html);
    }

    public function testModulePageCanSubmitTryRequestWithoutJavascript(): void
    {
        $app = new ApiExplorerTestApplication();
        $app->initialize();
        $app->getRouter()->add(
            RouteEntry::post("/api/echo",
                fn(Request $request): array => ["name" => $request->post()->string("name")]
            )
        );
        $app->registerModule(ApiExplorer::module(), "/dev/api");

        $request = new Request("POST", "/dev/api/explorer", [], [
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
            RouteEntry::get("/api/echo",
                fn(Request $request): array => ["name" => $request->query()->string("name")]
            )
        );
        $app->registerModule(ApiExplorer::module(), "/dev/api");

        $request = new Request("POST", "/dev/api/explorer", [], [
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
        $app->getRouter()->add(RouteEntry::get("/internal/ping", fn(): string => "pong"));
        $app->getRouter()->add(RouteEntry::get("/api/ping", fn(): string => "pong"));
        $app->registerModule(ApiExplorer::module("/internal"), "/dev/api");

        $context = new InjectionContext();
        $context->set(MatchedRoute::class, new MatchedRoute($this->routeByPath($app->getRouter(), "/dev/api/explorer")));
        $html = $app->getInjector()->get(PageRenderer::class, $context)->render(ApiExplorerPage::class);

        $this->assertStringContainsString("/internal/ping", $html);
        $this->assertStringNotContainsString("/api/ping", $html);
        $this->assertStringContainsString("1 operation", $html);
    }

    public function testExplorerRootRedirectsToPage(): void
    {
        $router = new Router();
        $entry = ApiExplorer::register($router, "/atom/api");
        $action = $entry->getRouteAction();

        $this->assertSame(ApiExplorerRedirectHandler::class, $action->controllerType);
        $this->assertSame("redirect", $action->methodName);

        $response = (new ApiExplorerRedirectHandler())->redirect(new MatchedRoute($entry), new Response());

        $this->assertSame(302, $response->getStatus());
        $this->assertSame(["/atom/api/explorer"], $response->headers()->all("Location"));
    }

    private function registerDocumentedApiRoutes(Router $router): void
    {
        $router->add(
            RouteEntry::get("/api/articles", [ApiExplorerArticlesController::class, "index"])
                ->name("articles.index")
                ->description("Returns published articles with optional search and paging.")
        );
        $router->add(
            RouteEntry::post("/api/articles", [ApiExplorerArticlesController::class, "create"])
                ->name("articles.create")
                ->description("Creates a draft article and returns the stored model.")
        );
        $router->add(
            RouteEntry::delete("/api/articles/{id}", [ApiExplorerArticlesController::class, "delete"])
                ->name("articles.item")
                ->description("Reads, updates, or deletes a single article.")
        );
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

final class ApiExplorerArticlesController
{
    #[ResponseOf(ApiExplorerArticleResponse::class)]
    public function index(ApiExplorerArticleListRequest $query): ApiExplorerPageResponse
    {
        return new ApiExplorerPageResponse();
    }

    #[ErrorResponse(422, ApiExplorerValidationErrorResponse::class, "Request body failed validation.")]
    public function create(ApiExplorerCreateArticleRequest $request): ApiExplorerArticleResponse
    {
        return new ApiExplorerArticleResponse();
    }

    #[ErrorResponse(404, ApiExplorerNotFoundResponse::class, "Article was not found.")]
    public function delete(#[FromRoute] int $id): ApiExplorerArticleResponse
    {
        return new ApiExplorerArticleResponse();
    }
}

#[Dto]
final class ApiExplorerArticleListRequest
{
    #[FromQuery("q")]
    public ?string $search = null;

    #[FromQuery]
    public int $page = 1;
}

#[Dto]
final class ApiExplorerCreateArticleRequest
{
    #[FromBody]
    #[Required]
    #[MaxLength(120)]
    public string $title;
}

final class ApiExplorerPageResponse
{
    #[ArrayOf]
    public array $items = [];

    public int $total;
    public int $page;
}

final class ApiExplorerArticleResponse
{
    public int $id;
    public string $title;
    public ApiExplorerAuthorResponse $author;
}

final class ApiExplorerAuthorResponse
{
    public int $id;
    public string $name;
}

final class ApiExplorerValidationErrorResponse
{
    public string $message;

    #[ArrayOf(ApiExplorerValidationErrorItemResponse::class)]
    public array $errors = [];
}

final class ApiExplorerValidationErrorItemResponse
{
    public string $field;
    public string $message;
}

final class ApiExplorerNotFoundResponse
{
    public string $message;
}
