<?php

declare(strict_types=1);

namespace Atom\Tests\Module;

use Atom\Application;
use Atom\Console\BufferedConsoleOutput;
use Atom\Di\Injector;
use Atom\Http\Response;
use Atom\Module\ModuleRegistry;
use Atom\Modules\Client\Client;
use Atom\Modules\Client\ClientScripts;
use Atom\Modules\Components\Components;
use Atom\Modules\Components\ComponentsStyles;
use Atom\Modules\Components\FieldError;
use Atom\Modules\Components\TextArea;
use Atom\Modules\Components\TextInput;
use Atom\Modules\Components\Progress;
use Atom\Modules\Components\RadioField;
use Atom\Modules\Components\Skeleton;
use Atom\Modules\Components\Spinner;
use Atom\Modules\Components\SwitchField;
use Atom\Modules\Components\ValidationSummary;
use Atom\Modules\DevReload\DevReloadModule;
use Atom\Modules\DevReload\DevReloadWatcher;
use Atom\Modules\ErrorPages\ErrorPagesModule;
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Http\StaticFileHandler;
use Atom\Router\RouteEntry;
use Atom\Router\RouteMatcher;
use Atom\Tests\Page\PageFixtures\HelloPage;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use Atom\Http\Request;
use Atom\Support\Paths;
use Atom\Queue\JobDispatcherInterface;
use Atom\Queue\JobInterface;
use Atom\Queue\JobRegistry;
use Atom\Scheduler\Schedule;
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

    public function testComponentsModuleRegistersSharedComponentsAndResources(): void
    {
        $app = new ModuleTestApplication();
        $app->initialize();

        $app->registerModule(Components::module());

        $components = $app->getInjector()->get(ComponentRegistry::class);

        $this->assertSame(FieldError::class, $components->get("FieldError"));
        $this->assertSame(ComponentsStyles::class, $components->get("ComponentsStyles"));
        $this->assertSame(TextArea::class, $components->get("TextArea"));
        $this->assertSame(TextInput::class, $components->get("TextInput"));
        $this->assertSame(ValidationSummary::class, $components->get("ValidationSummary"));
        $this->assertSame(RadioField::class, $components->get("RadioField"));
        $this->assertSame(SwitchField::class, $components->get("SwitchField"));
        $this->assertSame(Progress::class, $components->get("Progress"));
        $this->assertSame(Spinner::class, $components->get("Spinner"));
        $this->assertSame(Skeleton::class, $components->get("Skeleton"));
        $this->assertTrue(
            (new RouteMatcher($app->getRouter()))->match("GET", "/atom/components/resources/atom.css")->isFound()
        );
    }

    public function testClientModuleRegistersOnlyBrowserResources(): void
    {
        $app = new ModuleTestApplication();
        $app->initialize();

        $app->registerModule(Client::module());

        $this->assertTrue(
            (new RouteMatcher($app->getRouter()))->match("GET", "/atom/client/resources/atom.js")->isFound()
        );
        $this->assertSame(
            ClientScripts::class,
            $app->getInjector()->get(ComponentRegistry::class)->get("ClientScripts")
        );
        $this->assertFalse($app->getInjector()->get(ComponentRegistry::class)->has("Button"));
    }

    public function testDevReloadModuleRegistersVersionEndpointAndResources(): void
    {
        $directory = $this->tempDirectory();
        file_put_contents($directory . DIRECTORY_SEPARATOR . "page.atom.html", "<p>Hello</p>");

        $app = new ModuleTestApplication();
        $app->initialize();
        $app->getPaths()->alias("watched", $directory);

        $app->registerModule(new DevReloadModule(["@watched"]), "/atom/dev");

        $version = $app->handle(new Request("GET", "/atom/dev/reload-version"));
        $script = $app->handle(new Request("GET", "/atom/dev/resources/reload.js"));

        $this->assertSame(200, $version->getStatus());
        $this->assertSame("application/json", $version->headers()->get("Content-Type"));
        $this->assertStringContainsString('"version"', $version->getContent());
        $this->assertSame(200, $script->getStatus());
        $this->assertSame("application/javascript; charset=utf-8", $script->headers()->get("Content-Type"));
        $this->assertStringContainsString("window.location.reload()", $script->getContent());
    }

    public function testDevReloadWatcherVersionChangesWhenWatchedFilesChange(): void
    {
        $directory = $this->tempDirectory();
        $file = $directory . DIRECTORY_SEPARATOR . "page.atom.html";
        file_put_contents($file, "<p>Before</p>");

        $paths = new Paths();
        $paths->alias("watched", $directory);
        $watcher = new DevReloadWatcher($paths);
        $before = $watcher->version(["@watched"]);

        sleep(1);
        file_put_contents($file, "<p>After</p>");

        $this->assertNotSame($before, $watcher->version(["@watched"]));
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

        $entries = $context->pages(__DIR__ . "/../Page/PageFixtures", [
            \Atom\Security\CsrfMiddleware::class,
        ]);
        $match = (new RouteMatcher($app->getRouter()))->match("GET", "/dev/hello-page");

        $this->assertCount(1, $entries);
        $this->assertTrue($match->isFound());
        $this->assertSame(HelloPage::class, $entries[0]->getMetadataOfType(\Atom\Page\PageRouteMetadata::class)?->pageClass);
        $this->assertSame([\Atom\Security\CsrfMiddleware::class], $entries[0]->getOwnMiddlewares());
    }

    public function testApplicationRegistersModulesFromHookBeforeBootstrap(): void
    {
        $app = new HookModuleApplication();
        $app->initialize();

        $this->assertSame("module-service", $app->moduleServiceName);
        $this->assertSame("/hook/module", $app->getRouter()->getRoutes()[0]->getFullPath());
        $this->assertCount(2, $app->getModules()->all());
        $this->assertInstanceOf(ErrorPagesModule::class, $app->getModules()->all()[0]->module);
    }

    public function testInstalledModuleContributesConsoleCommands(): void
    {
        $app = new ModuleCommandTestApplication();
        $app->initialize();
        $output = new BufferedConsoleOutput();

        $code = $app->getConsole()->run(["atom", "module:hello", "Edin"], $output);

        $this->assertTrue($app->getConsole()->commands()->has("module:hello"));
        $this->assertSame(0, $code);
        $this->assertSame("Hello Edin from module" . PHP_EOL, $output->output());
    }

    public function testInstalledModuleRegistersJobsInSharedRegistry(): void
    {
        ModuleFixtureJob::$handled = false;
        $app = new ModuleJobTestApplication();
        $app->initialize();

        $registry = $app->getInjector()->get(JobRegistry::class);
        $this->assertSame(ModuleFixtureJob::class, $registry->resolve("module.fixture"));

        $app->getInjector()
            ->get(JobDispatcherInterface::class)
            ->dispatch(new ModuleFixtureJob());

        $this->assertTrue(ModuleFixtureJob::$handled);
    }

    public function testInstalledModuleContributesScheduledTasks(): void
    {
        $app = new ModuleScheduleTestApplication();
        $app->initialize();

        $tasks = $app->getInjector()->get(Schedule::class)->tasks();
        $this->assertCount(1, $tasks);
        $this->assertSame("module:cleanup", $tasks[0]->summary());
        $this->assertSame("0 0 * * *", $tasks[0]->expression());
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

final class ModuleCommandTestApplication extends Application
{
    protected function modules(ModuleRegistry $modules): void
    {
        $modules->add(new ModuleCommandTestModule());
    }
}

final readonly class ModuleCommandTestModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->commands(
            __DIR__ . "/Fixtures/Commands",
            "Atom\\Tests\\Module\\Fixtures\\Commands"
        );
    }
}

final class ModuleJobTestApplication extends Application
{
    protected function modules(ModuleRegistry $modules): void
    {
        $modules->add(new ModuleJobTestModule());
    }
}

final readonly class ModuleJobTestModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->jobs(ModuleFixtureJob::class);
    }
}

final class ModuleFixtureJob implements JobInterface
{
    public static bool $handled = false;

    public static function type(): string
    {
        return "module.fixture";
    }

    public function payload(): array
    {
        return [];
    }

    public static function fromPayload(array $payload): self
    {
        return new self();
    }

    public function handle(): void
    {
        self::$handled = true;
    }
}

final class ModuleScheduleTestApplication extends Application
{
    protected function modules(ModuleRegistry $modules): void
    {
        $modules->add(new ModuleScheduleTestModule());
    }
}

final readonly class ModuleScheduleTestModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->schedule(static function (Schedule $schedule): void {
            $schedule->command("module:cleanup")->daily();
        });
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
