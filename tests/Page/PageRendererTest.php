<?php

declare(strict_types=1);

namespace Atom\Tests\Page;

use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Application;
use Atom\Http\Request;
use Atom\Modules\Framework\Components\DialogModel;
use Atom\Modules\Framework\Components\SidePanelModel;
use Atom\Modules\Framework\Components\TabsModel;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Hydrator\Attributes\FromQuery;
use Atom\Hydrator\Attributes\FromRoute;
use Atom\Http\Response;
use Atom\Page\FormModel;
use Atom\Page\Page;
use Atom\Page\PageAction;
use Atom\Page\PageActionException;
use Atom\Page\PageActionHandler;
use Atom\Page\PageRenderer;
use Atom\Page\PageRoute;
use Atom\Page\PageRouteHandler;
use Atom\Page\PageRouteMetadata;
use Atom\Page\PageRouteRegistrar;
use Atom\Page\State;
use Atom\Profiler\Profile;
use Atom\Router\Route;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Router\RouteMatcher;
use Atom\Router\Router;
use Atom\Validation\ValidationError;
use Atom\Validation\ValidationResult;
use Atom\Validation\Rules\MinLength;
use Atom\Validation\Rules\Required;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/DefaultRegistration/DefaultPageRegistration.php";

final class PageRendererTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::$app = null;
        Profile::reset();
    }

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

    public function testPageRendererExposesCurrentRequestPathToTemplate(): void
    {
        $context = new InjectionContext();
        $context->set(Request::class, new Request("GET", "/admin/articles"));
        $renderer = new PageRenderer(new Injector(), $context);

        $html = $renderer->render(CurrentPathRenderedTestPage::class);

        $this->assertSame("<span>/admin/articles</span>\n", $html);
    }

    public function testPageRendererCachesParsedTemplates(): void
    {
        $renderer = new PageRenderer(new Injector(), new InjectionContext());

        $first = $renderer->render(RenderedTestPage::class);
        $second = $renderer->render(RenderedTestPage::class);

        $parseSpans = array_filter(
            Profile::profiler()->spans(),
            static fn($span): bool => $span->name === "view.parse"
        );

        $this->assertSame($first, $second);
        $this->assertCount(1, $parseSpans);
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

    public function testPageActionHandlerParsesQuotedActionArguments(): void
    {
        $html = $this->handlePageAction(
            ParsedArgumentRenderedTestPage::class,
            "/parsed-argument-page",
            'set("hello, world", \'draft, yes\', true, 1.5)'
        );

        $this->assertSame("<h1>hello, world|draft, yes|yes|1.5</h1>\n", $html);
    }

    public function testPageActionHandlerCanInvokeNestedModelAction(): void
    {
        $html = $this->handlePageAction(
            NestedActionRenderedTestPage::class,
            "/nested-action-page",
            "toast.close()"
        );

        $this->assertSame("<h1>closed</h1>\n", $html);
    }

    public function testPageActionHandlerCanInvokeDialogModelActions(): void
    {
        $opened = $this->handlePageAction(
            DialogModelRenderedTestPage::class,
            "/dialog-model-page",
            "dialog.open(12)"
        );

        $this->assertStringContainsString("<h1>open|12</h1>\n", $opened);

        $page = new DialogModelRenderedTestPage();
        $page->dialog->open(12);
        $state = (new \Atom\Page\JsonPageStateSerializer())->serialize($page);

        $closed = $this->handlePageAction(
            DialogModelRenderedTestPage::class,
            "/dialog-model-page",
            "dialog.close()",
            ["_state" => $state]
        );

        $this->assertStringContainsString("<h1>closed|12</h1>\n", $closed);
    }

    public function testPageActionHandlerCanInvokeSidePanelModelActions(): void
    {
        $opened = $this->handlePageAction(
            SidePanelModelRenderedTestPage::class,
            "/side-panel-model-page",
            "editor.open(12)"
        );

        $this->assertStringContainsString("<h1>open|12</h1>\n", $opened);

        $page = new SidePanelModelRenderedTestPage();
        $page->editor->open(12);
        $state = (new \Atom\Page\JsonPageStateSerializer())->serialize($page);

        $closed = $this->handlePageAction(
            SidePanelModelRenderedTestPage::class,
            "/side-panel-model-page",
            "editor.close()",
            ["_state" => $state]
        );

        $this->assertStringContainsString("<h1>closed|12</h1>\n", $closed);
    }

    public function testPageActionHandlerCanInvokeTabsModelAction(): void
    {
        $html = $this->handlePageAction(
            TabsModelRenderedTestPage::class,
            "/tabs-model-page",
            "tabs.select('source')"
        );

        $this->assertStringContainsString("<h1>source</h1>\n", $html);
    }

    public function testPageRouteRegistrarRegistersActionsFromTypedPageModels(): void
    {
        $router = new Router();
        (new PageRouteRegistrar())->register($router, [
            new \Atom\Page\PageDescriptor("/tabs-model-page", TabsModelRenderedTestPage::class),
        ]);

        $match = (new RouteMatcher($router))->match("POST", "/tabs-model-page");

        $this->assertTrue($match->isFound());
        $this->assertSame(PageActionHandler::class, $match->matchedRoute->getRouteAction()->controllerType);
    }

    public function testPageRouteRegistrarRegistersActionsFromNestedTypedPageModels(): void
    {
        $router = new Router();
        (new PageRouteRegistrar())->register($router, [
            new \Atom\Page\PageDescriptor("/nested-state-page", NestedStateRenderedTestPage::class),
        ]);

        $match = (new RouteMatcher($router))->match("POST", "/nested-state-page");

        $this->assertTrue($match->isFound());
        $this->assertSame(PageActionHandler::class, $match->matchedRoute->getRouteAction()->controllerType);
    }

    public function testPageActionHandlerCanInvokeNestedTypedModelAction(): void
    {
        $html = $this->handlePageAction(
            NestedStateRenderedTestPage::class,
            "/nested-state-page",
            "admin.editor.open(99)"
        );

        $this->assertStringContainsString("<h1>open|99</h1>\n", $html);
    }

    public function testPageActionHandlerBindsActionParametersFromRequest(): void
    {
        $html = $this->handlePageAction(ActionParameterRenderedTestPage::class, "/action-parameter-page/{id}", "save", [
            "title" => "  Atom  ",
            "published" => "true",
        ], [
            "mode" => "preview",
        ]);

        $this->assertSame("<h1>7 Atom preview yes</h1>\n", $html);
    }

    public function testPageActionHandlerBindsImplicitScalarActionParameters(): void
    {
        $html = $this->handlePageAction(ImplicitActionParameterRenderedTestPage::class, "/implicit-action-page/{id}", "save", [
            "title" => "Atom",
            "published" => "1",
        ]);

        $this->assertSame("<h1>7 Atom yes</h1>\n", $html);
    }

    public function testPageActionHandlerReportsInvalidActionParameter(): void
    {
        $this->expectException(PageActionException::class);
        $this->expectExceptionMessage("Unable to bind parameter 'id'");
        $this->expectExceptionMessage("expected int");

        $this->handlePageAction(ImplicitActionParameterRenderedTestPage::class, "/implicit-action-page/{id}", "save", [
            "title" => "Atom",
        ], query: [], routeValues: ["id" => "nope"]);
    }

    public function testPageActionMayReturnResponse(): void
    {
        $result = $this->handlePageAction(ResponseActionRenderedTestPage::class, "/response-action-page", "save");

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame("saved", $result->getContent());
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

    public function testPageActionInvokesGetBeforeActionSoQueryStateIsPreserved(): void
    {
        $serializer = new \Atom\Page\JsonPageStateSerializer();
        $page = new QueryAndStateRenderedTestPage();
        $page->localTab = "source";
        $state = $serializer->serialize($page);

        $router = new Router();
        (new PageRouteRegistrar())->register($router, [
            new \Atom\Page\PageDescriptor("/tabs", QueryAndStateRenderedTestPage::class),
        ]);
        $match = (new RouteMatcher($router))->match("POST", "/tabs", ["tab" => "drafts"]);

        $context = new InjectionContext();
        $context->set(Request::class, new Request("POST", "/tabs", ["tab" => "drafts"], [
            "_action" => "setLocalTab('history')",
            "_state" => $state,
        ]));
        $context->set(\Atom\Router\MatchedRoute::class, $match->matchedRoute);
        $injector = new Injector();
        $handler = new PageActionHandler($injector, $context);
        $renderer = new PageRenderer($injector, $context);

        $html = $handler->handle($renderer, $match->matchedRoute, $context->get(Request::class));

        $this->assertStringContainsString("<h1>drafts|history</h1>", $html);
    }

    public function testPageActionHandlerHydratesPageInputBeforeAction(): void
    {
        $html = $this->handlePageAction(BoundInputRenderedTestPage::class, "/bound-page/{id}", "save", [
            "name" => "  Atom  ",
            "published" => "yes",
        ], [
            "mode" => "draft",
        ]);

        $this->assertSame("<h1>7 Atom draft yes</h1>\n", $html);
    }

    public function testPageActionHandlerHydratesStateBackedPageInputBeforeAction(): void
    {
        $html = $this->handlePageAction(FilterRenderedTestPage::class, "/filter-page", "filter", [
            "query" => "  ada  ",
            "status" => "Active",
        ]);

        $this->assertStringContainsString("<h1>ada|Active</h1>", $html);
        $this->assertStringContainsString('name="atom-state"', $html);
    }

    public function testPageActionHandlerHydratesFormModelBeforeAction(): void
    {
        $html = $this->handlePageAction(FormModelRenderedTestPage::class, "/form-model-page", "save", [
            "title" => "  Atom Forms  ",
            "summary" => "Tiny but useful",
        ]);

        $this->assertStringContainsString("<h1>Atom Forms|Tiny but useful</h1>", $html);
    }

    public function testPageStateRestoresTypedFormModel(): void
    {
        $serializer = new \Atom\Page\JsonPageStateSerializer();
        $page = new FormModelRenderedTestPage();
        $page->edit->title = "Draft";
        $state = $serializer->serialize($page);

        $restored = new FormModelRenderedTestPage();
        $serializer->deserialize($restored, $state);

        $this->assertSame("Draft", $restored->edit->title);
        $this->assertInstanceOf(PageEditForm::class, $restored->edit);
    }

    public function testPageActionHandlerReportsInvalidPageInput(): void
    {
        $this->expectException(PageActionException::class);
        $this->expectExceptionMessage("Unable to hydrate page input");
        $this->expectExceptionMessage("expected int");

        $this->handlePageAction(InvalidBoundInputRenderedTestPage::class, "/invalid-bound-page", "save", [
            "age" => "nope",
        ]);
    }

    public function testPageHasEmptyValidationErrorsBeforeValidation(): void
    {
        $page = new ValidatedRenderedTestPage();

        $this->assertTrue($page->errors()->passed());
        $this->assertFalse($page->errors()->has("title"));
        $this->assertNull($page->errors()->first("title"));
    }

    public function testPageCanValidateItselfWithAttributeRules(): void
    {
        $page = new ValidatedRenderedTestPage();
        $page->title = "";

        $this->assertFalse($page->validate());
        $this->assertTrue($page->errors()->failed());
        $this->assertTrue($page->errors()->has("title"));
        $this->assertSame("required", $page->errors()->errorsFor("title")[0]->code);
    }

    public function testPageValidationPassesForValidState(): void
    {
        $page = new ValidatedRenderedTestPage();
        $page->title = "Valid title";

        $this->assertTrue($page->validate());
        $this->assertTrue($page->errors()->passed());
    }

    public function testPageCanStoreCustomValidationResult(): void
    {
        $page = new CustomValidatedRenderedTestPage();

        $page->failTitle();

        $this->assertTrue($page->errors()->has("title"));
        $this->assertSame("Nope.", $page->errors()->first("title"));
    }

    public function testPageCanStoreTransientFlashMessage(): void
    {
        $page = new CustomValidatedRenderedTestPage();

        $page->flash("Saved.", "success", "Article saved");

        $this->assertTrue($page->hasFlash());
        $this->assertSame("Saved.", $page->flashMessage());
        $this->assertSame("Article saved", $page->flashTitle());
        $this->assertSame("success", $page->flashVariant());

        $page->clearFlash();

        $this->assertFalse($page->hasFlash());
        $this->assertSame("", $page->flashMessage());
        $this->assertSame("", $page->flashTitle());
        $this->assertSame("success", $page->flashVariant());
    }

    public function testPageCanValidateModelWithAttributeRules(): void
    {
        $page = new ModelValidatedRenderedTestPage();
        $page->form->title = "";

        $this->assertFalse($page->validateForm());
        $this->assertTrue($page->errors()->has("title"));
        $this->assertSame("required", $page->errors()->errorsFor("title")[0]->code);
    }

    public function testPageActionCanValidateHydratedInput(): void
    {
        $html = $this->handlePageAction(ValidatedRenderedTestPage::class, "/validated-page", "save", [
            "title" => "",
        ]);

        $this->assertSame("<h1>invalid: required</h1>\n", $html);
    }

    public function testPageActionHandlerReportsInvalidActionExpression(): void
    {
        $this->expectException(PageActionException::class);
        $this->expectExceptionMessage("Expected syntax like 'save'");

        $this->handlePageAction(PostRenderedTestPage::class, "/post-page", "save(");
    }

    public function testPageActionHandlerReportsAvailableActionsWhenActionIsMissing(): void
    {
        $this->expectException(PageActionException::class);
        $this->expectExceptionMessage("Page action 'publish' was not found");
        $this->expectExceptionMessage("Available actions: save.");

        $this->handlePageAction(PostRenderedTestPage::class, "/post-page", "publish");
    }

    public function testPageActionHandlerReportsHttpMethodMismatch(): void
    {
        $this->expectException(PageActionException::class);
        $this->expectExceptionMessage("is not available for POST");
        $this->expectExceptionMessage("Available method: GET.");

        $this->handlePageAction(GetActionRenderedTestPage::class, "/get-action-page", "refresh");
    }

    public function testPageActionHandlerReportsTooManyActionArguments(): void
    {
        $this->expectException(PageActionException::class);
        $this->expectExceptionMessage("was called with 3 argument(s)");
        $this->expectExceptionMessage("accepts 2");

        $this->handlePageAction(DeleteRenderedTestPage::class, "/delete-page", 'delete(12, "hard", true)');
    }

    public function testPageActionHandlerWrapsMissingRequiredParameterErrors(): void
    {
        $this->expectException(PageActionException::class);
        $this->expectExceptionMessage("Unable to bind parameter 'id'");
        $this->expectExceptionMessage("is required");

        $this->handlePageAction(DeleteRenderedTestPage::class, "/delete-page", "delete");
    }

    public function testRendersPageInsideConfiguredLayoutComponent(): void
    {
        $renderer = new PageRenderer(new Injector(), new InjectionContext());

        $html = $renderer->render(LayoutRenderedTestPage::class);

        $this->assertSame("<layout title=\"Body\"><h1>Body</h1>\n</layout>", $html);
    }

    private function handlePageAction(
        string $pageClass,
        string $path,
        string $action,
        array $body = [],
        array $query = [],
        array $routeValues = ["id" => "7"]
    ): mixed
    {
        $router = new Router();
        (new PageRouteRegistrar())->register($router, [
            new \Atom\Page\PageDescriptor($path, $pageClass),
        ]);
        $routeValue = reset($routeValues) ?: "7";
        $requestPath = preg_replace('/\{[^}]+}/', (string) $routeValue, $path) ?? $path;
        $match = (new RouteMatcher($router))->match("POST", $requestPath);
        if (!$match->isFound()) {
            $entry = RouteEntry::create("POST", $path, RouteAction::method(PageActionHandler::class, "handle"))
                ->metadata(new PageRouteMetadata($pageClass));
            $match = \Atom\Router\RouteMatchResult::found(new \Atom\Router\MatchedRoute($entry, $routeValues));
        }

        $body["_action"] = $action;
        $context = new InjectionContext();
        $context->set(Request::class, new Request("POST", $requestPath, $query, $body));
        $context->set(\Atom\Router\MatchedRoute::class, $match->matchedRoute);
        $injector = new Injector();

        return (new PageActionHandler($injector, $context))->handle(
            new PageRenderer($injector, $context),
            $match->matchedRoute,
            $context->get(Request::class)
        );
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

    public function testPageRouteMiddlewareAppliesToRenderAndActionRoutes(): void
    {
        $router = new Router();
        $descriptor = new \Atom\Page\PageDescriptor(
            "/articles",
            \Atom\Tests\View\PageFixtures\ArticleListPage::class,
            middlewares: [\Atom\Security\CsrfMiddleware::class]
        );

        $entries = (new PageRouteRegistrar())->register($router, [$descriptor]);

        $this->assertCount(2, $entries);
        foreach ($entries as $entry) {
            $this->assertSame([\Atom\Security\CsrfMiddleware::class], $entry->getOwnMiddlewares());
        }
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

final class ParsedArgumentRenderedTestPage extends Page
{
    public string $title = "Before";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("set")]
    public function set(string $name, string $mode, bool $published, float $score): void
    {
        $this->title = $name . "|" . $mode . "|" . ($published ? "yes" : "no") . "|" . $score;
    }
}

final class ActionParameterRenderedTestPage extends Page
{
    public string $title = "";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("save")]
    public function save(
        #[FromRoute]
        int $id,
        #[FromBody]
        string $title,
        #[FromQuery]
        string $mode,
        #[FromBody]
        bool $published
    ): void {
        $this->title = $id . " " . $title . " " . $mode . " " . ($published ? "yes" : "no");
    }
}

final class ImplicitActionParameterRenderedTestPage extends Page
{
    public string $title = "";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("save")]
    public function save(int $id, string $title, bool $published = false): void
    {
        $this->title = $id . " " . $title . " " . ($published ? "yes" : "no");
    }
}

final class ResponseActionRenderedTestPage extends Page
{
    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("save")]
    public function save(): Response
    {
        return (new Response())->content("saved");
    }
}

final class GetActionRenderedTestPage extends Page
{
    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("refresh", method: "get")]
    public function refresh(): void
    {
    }
}

final class BoundInputRenderedTestPage extends Page
{
    public string $title = "";

    #[FromRoute("id")]
    public int $id = 0;

    #[FromBody]
    public string $name = "";

    #[FromBody]
    public bool $published = false;

    #[FromQuery]
    public string $mode = "";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("save")]
    public function save(): void
    {
        $this->title = $this->id . " " . $this->name . " " . $this->mode . " " . ($this->published ? "yes" : "no");
    }
}

final class FilterRenderedTestPage extends Page
{
    #[State]
    #[FromBody]
    public string $query = "";

    #[State]
    #[FromBody]
    public string $status = "";

    public string $title = "";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("filter")]
    public function filter(): void
    {
        $this->title = $this->query . "|" . $this->status;
    }
}

final class InvalidBoundInputRenderedTestPage extends Page
{
    #[FromBody]
    public int $age;

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("save")]
    public function save(): void
    {
    }
}

final class FormModelRenderedTestPage extends Page
{
    public string $title = "";

    #[State]
    #[FormModel]
    public PageEditForm $edit;

    public function __construct()
    {
        $this->edit = new PageEditForm();
    }

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("save")]
    public function save(): void
    {
        $this->title = $this->edit->title . "|" . $this->edit->summary;
    }
}

final class PageEditForm
{
    public string $title = "";

    public string $summary = "";
}

final class NestedActionRenderedTestPage extends Page
{
    public NestedActionToastModel $toast;

    public function __construct()
    {
        $this->toast = new NestedActionToastModel();
    }

    public function template(): ?string
    {
        return "NestedActionRenderedTestPage.atom.html";
    }
}

final class NestedActionToastModel
{
    public bool $open = true;

    #[PageAction]
    public function close(): void
    {
        $this->open = false;
    }
}

final class DialogModelRenderedTestPage extends Page
{
    #[State]
    public DialogModel $dialog;

    public function __construct()
    {
        $this->dialog = new DialogModel();
    }

    public function template(): ?string
    {
        return "DialogModelRenderedTestPage.atom.html";
    }
}

final class SidePanelModelRenderedTestPage extends Page
{
    #[State]
    public SidePanelModel $editor;

    public function __construct()
    {
        $this->editor = new SidePanelModel();
    }

    public function template(): ?string
    {
        return "SidePanelModelRenderedTestPage.atom.html";
    }
}

final class TabsModelRenderedTestPage extends Page
{
    #[State]
    public TabsModel $tabs;

    public function __construct()
    {
        $this->tabs = new TabsModel("preview");
    }

    public function template(): ?string
    {
        return "TabsModelRenderedTestPage.atom.html";
    }
}

final class NestedStateRenderedTestPage extends Page
{
    #[State]
    public NestedAdminState $admin;

    public function __construct()
    {
        $this->admin = new NestedAdminState();
    }

    public function template(): ?string
    {
        return "NestedStateRenderedTestPage.atom.html";
    }
}

final class NestedAdminState
{
    public SidePanelModel $editor;

    public function __construct()
    {
        $this->editor = new SidePanelModel();
    }
}

final class ValidatedRenderedTestPage extends Page
{
    #[FromBody]
    #[Required]
    #[MinLength(3)]
    public string $title = "";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    #[PageAction("save")]
    public function save(): void
    {
        $this->title = $this->validate()
            ? "valid"
            : "invalid: " . $this->errors()->errorsFor("title")[0]->code;
    }
}

final class CustomValidatedRenderedTestPage extends Page
{
    public function failTitle(): void
    {
        $this->setValidation(
            ValidationResult::valid()->add(new ValidationError("title", "Nope.", "custom"))
        );
    }
}

final class ModelValidatedRenderedTestPage extends Page
{
    public ModelValidatedForm $form;

    public function __construct()
    {
        $this->form = new ModelValidatedForm();
    }

    public function validateForm(): bool
    {
        return $this->validateModel($this->form);
    }
}

final class ModelValidatedForm
{
    #[Required]
    public string $title = "";
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

final class QueryAndStateRenderedTestPage extends Page
{
    #[State]
    public string $localTab = "preview";

    public string $tab = "overview";

    public string $title = "";

    public function template(): ?string
    {
        return "GetRenderedTestPage.atom.html";
    }

    public function get(Request $request): void
    {
        $this->tab = $request->query()->string("tab", "overview");
        $this->title = $this->tab . "|" . $this->localTab;
    }

    #[PageAction("setLocalTab")]
    public function setLocalTab(string $tab): void
    {
        $this->localTab = $tab;
        $this->title = $this->tab . "|" . $this->localTab;
    }
}

final class CurrentPathRenderedTestPage extends Page
{
    public function template(): ?string
    {
        return "CurrentPathRenderedTestPage.atom.html";
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
