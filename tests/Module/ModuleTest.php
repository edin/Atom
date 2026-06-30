<?php

declare(strict_types=1);

namespace Atom\Tests\Module;

use Atom\Application;
use Atom\Di\Injector;
use Atom\Http\Response;
use Atom\Module\ModuleRegistry;
use Atom\Modules\Framework\Components\FieldError;
use Atom\Modules\Framework\Components\ValidationSummary;
use Atom\Modules\Framework\Framework;
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Http\StaticFileHandler;
use Atom\Router\RouteEntry;
use Atom\Router\RouteMatcher;
use Atom\Tests\Page\PageFixtures\HelloPage;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleTest extends TestCase
{
    protected function setUp(): void
    {
        Application::$app = null;
    }

    protected function tearDown(): void
    {
        Application::$app = null;
    }

    public function testModuleContextRegistersRoutesAndComponents(): void
    {
        $app = new ModuleTestApplication();
        $app->initialize();

        $app->registerModule(new TestModule());

        $this->assertSame("/module", $app->getRouter()->getRoutes()[0]->getFullPath());
        $this->assertSame(TestComponent::class, $app->getInjector()->get(ComponentRegistry::class)->get("TestComponent"));
        $this->assertSame("module-service", $app->getInjector()->get(TestModuleService::class)->name);
        $this->assertTrue($app->getInjector()->has(TestModuleService::class));
    }

    public function testModuleContextRegistersResources(): void
    {
        $directory = $this->tempDirectory();
        file_put_contents($directory . DIRECTORY_SEPARATOR . "module.css", "body { color: red; }");

        $app = new ModuleTestApplication();
        $app->initialize();

        $context = new ModuleContext(
            $app->getRouter(),
            $app->getInjector(),
            $app->getInjector()->get(ComponentRegistry::class),
            "/dev"
        );
        $entries = $context->resources("/resources", $directory);

        $this->assertCount(1, $entries);
        $this->assertSame("/dev/resources/{path*}", $entries[0]->getFullPath());
        $this->assertSame($entries, $context->resources("/resources", $directory));

        $match = (new RouteMatcher($app->getRouter()))->match("GET", "/dev/resources/module.css");
        $this->assertTrue($match->isFound());
        $this->assertSame(["path" => "module.css"], $match->matchedRoute->getRouteParams());

        $response = (new StaticFileHandler())->serve($match->matchedRoute, new Response());

        $this->assertSame("text/css; charset=utf-8", $response->headers()->get("Content-Type"));
        $this->assertSame("body { color: red; }", $response->getContent());
    }

    public function testModuleContextBuildsMountedPaths(): void
    {
        $app = new ModuleTestApplication();
        $app->initialize();

        $context = new ModuleContext(
            $app->getRouter(),
            $app->getInjector(),
            $app->getInjector()->get(ComponentRegistry::class),
            "/tools/blog"
        );

        $this->assertSame("/tools/blog", $context->mountedPath());
        $this->assertSame("/tools/blog/resources/app.css", $context->mountedPath("/resources/app.css"));
        $this->assertSame("/tools/blog/resources", $context->resourcePath());
        $this->assertSame("/tools/blog/assets/app.css", $context->resourcePath("/assets", "app.css"));
        $this->assertSame("/", $context->root()->mountedPath());
        $this->assertSame("/resources/app.css", $context->root()->resourcePath("/resources", "app.css"));
    }

    public function testFrameworkModuleRegistersSharedComponents(): void
    {
        $app = new ModuleTestApplication();
        $app->initialize();

        $app->registerModule(Framework::module());

        $components = $app->getInjector()->get(ComponentRegistry::class);

        $this->assertSame(FieldError::class, $components->get("FieldError"));
        $this->assertSame(ValidationSummary::class, $components->get("ValidationSummary"));
    }

    public function testModuleContextRegistersPagesUnderBasePath(): void
    {
        $app = new ModuleTestApplication();
        $app->initialize();

        $context = new ModuleContext(
            $app->getRouter(),
            $app->getInjector(),
            $app->getInjector()->get(ComponentRegistry::class),
            "/dev"
        );

        $entries = $context->pages(__DIR__ . "/../Page/PageFixtures");
        $match = (new RouteMatcher($app->getRouter()))->match("GET", "/dev/hello-page");

        $this->assertCount(1, $entries);
        $this->assertTrue($match->isFound());
        $this->assertSame(HelloPage::class, $entries[0]->getMetadataOfType(\Atom\Page\PageRouteMetadata::class)?->pageClass);
    }

    public function testApplicationRegistersModulesFromHookBeforeBootstrap(): void
    {
        $app = new HookModuleApplication();
        $app->initialize();

        $this->assertSame("module-service", $app->moduleServiceName);
        $this->assertSame("/hook/module", $app->getRouter()->getRoutes()[0]->getFullPath());
        $this->assertCount(1, $app->getModules()->all());
    }

    private function tempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_module_" . uniqid();
        mkdir($directory, 0777, true);

        return $directory;
    }
}

final class TestModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->bind(TestModuleService::class)->toSelf()->singleton();
        $context->route(RouteEntry::get("/module", fn(): string => "module"));
        $context->component("TestComponent", TestComponent::class);
    }
}

final readonly class TestModuleService
{
    public string $name;

    public function __construct()
    {
        $this->name = "module-service";
    }
}

final class TestComponent implements ComponentInterface
{
    public function render(): string
    {
        return "";
    }
}

final class ModuleTestApplication extends Application
{
    protected function bootstrap(Injector $injector): void
    {
    }
}

final class HookModuleApplication extends Application
{
    public ?string $moduleServiceName = null;

    protected function modules(ModuleRegistry $modules): void
    {
        $modules->add(new MountedTestModule(), "/hook");
    }

    protected function bootstrap(Injector $injector): void
    {
        $this->moduleServiceName = $injector->get(TestModuleService::class)->name;
    }
}

final class MountedTestModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->bind(TestModuleService::class)->toSelf()->singleton();
        $context->route(RouteEntry::get($context->mountedPath("/module"), fn(): string => "module"));
    }
}
