<?php

declare(strict_types=1);

namespace Atom\Tests;

use Atom\Application;
use Atom\Config\Config;
use Atom\Config\Options;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Di\ServiceProviderRegistry;
use Atom\Dispatcher\ResponseEmitterInterface;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Page\PageRegistry;
use Atom\Router\Route;
use Atom\Router\RouteMatcher;
use Atom\Support\Paths;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv("ATOM_APP_TEST_NAME");
        unset($_ENV["ATOM_APP_TEST_NAME"], $_SERVER["ATOM_APP_TEST_NAME"]);

        Application::$app = null;
        Route::clearRouter();
    }

    public function testInitializeRegistersServicesAndBootstrapsRouteFacade(): void
    {
        $app = new TestApplication();

        $app->initialize();

        $this->assertTrue($app->bootstrapped);
        $this->assertSame("ready", $app->getInjector()->get(TestApplicationMarker::class)->value);

        $match = (new RouteMatcher($app->getRouter()))->match("GET", "/hello/Atom");

        $this->assertTrue($match->isFound());
        $this->assertSame(["name" => "Atom"], $match->matchedRoute->getRouteParams());
    }

    public function testRunUsesRequestContextAndReturnsResponse(): void
    {
        $app = new TestApplication();

        $response = $app->run(new Request("GET", "/hello/Atom"));

        $this->assertSame("Hello Atom", $response->getContent());
        $this->assertSame($response, $app->emitter->response);
        $this->assertSame(["name" => "Atom"], $app->getCurrentRoute()?->getRouteParams());
    }

    public function testApplicationProvidesConsoleWithFrameworkCommands(): void
    {
        $app = new TestApplication();
        $app->initialize();

        $output = new \Atom\Console\BufferedConsoleOutput();
        $code = $app->getConsole()->run(["atom", "atom:about"], $output);

        $this->assertSame(0, $code);
        $this->assertSame("Atom Framework" . PHP_EOL, $output->output());
    }

    public function testEnvironmentFilesLoadBeforeConfigAndServices(): void
    {
        $path = tempnam(sys_get_temp_dir(), "atom_app_env_");
        $this->assertIsString($path);
        file_put_contents($path, "ATOM_APP_TEST_NAME=Atom\n");

        $app = new TestEnvironmentApplication($path);
        $app->initialize();

        $this->assertSame("Atom", $app->configuredName);
        $this->assertSame("Atom", $app->serviceName);
    }

    public function testOptionsClassesCanBeInjectedDirectly(): void
    {
        $app = new TestApplication();
        $app->initialize();
        $app->getConfig()->set(new TestEnvironmentOptions("Injected"));

        $service = $app->getInjector()->get(TestOptionsConsumer::class);

        $this->assertSame("Injected", $service->options->name);
    }

    public function testRootAndAppPathAliasesAreRegisteredByDefault(): void
    {
        $app = new TestApplication();
        $app->initialize();

        $root = str_replace("\\", "/", __DIR__);

        $this->assertSame($root, $app->getPaths()->resolve("@root"));
        $this->assertSame($root . "/app", $app->getPaths()->resolve("@app"));
    }

    public function testPathsAreConfiguredBeforeEnvironmentFiles(): void
    {
        $directory = sys_get_temp_dir() . "/atom_paths_" . uniqid();
        mkdir($directory);
        file_put_contents($directory . "/.env", "ATOM_APP_TEST_NAME=Alias\n");

        $app = new TestPathApplication($directory);
        $app->initialize();

        $this->assertSame("Alias", $app->configuredName);
        $this->assertSame($app->getPaths(), $app->getInjector()->get(Paths::class));
        $this->assertSame(str_replace("\\", "/", $directory . "/app"), $app->getPaths()->resolve("@app"));
    }

    public function testDefaultEnvironmentFileLoadsFromRootPath(): void
    {
        $directory = sys_get_temp_dir() . "/atom_default_env_" . uniqid();
        mkdir($directory);
        file_put_contents($directory . "/.env", "ATOM_APP_TEST_NAME=DefaultEnv\n");

        $app = new TestPathApplication($directory);
        $app->initialize();

        $this->assertSame("DefaultEnv", $app->configuredName);
    }

    public function testPagesAreRegisteredFromApplicationHookBeforeBootstrap(): void
    {
        $app = new TestPageApplication(__DIR__ . "/Page/PageFixtures");
        $app->initialize();

        $match = (new RouteMatcher($app->getRouter()))->match("GET", "/app/hello-page");

        $this->assertTrue($match->isFound());
        $this->assertTrue($app->pageRouteWasAvailableInBootstrap);
        $this->assertCount(1, $app->getPages()->directories());
    }

    public function testComponentsAreRegisteredFromApplicationHookBeforeBootstrap(): void
    {
        $app = new TestComponentApplication();
        $app->initialize();

        $components = $app->getInjector()->get(ComponentRegistry::class);

        $this->assertSame(TestApplicationComponent::class, $components->get("App.Test"));
        $this->assertTrue($app->componentWasAvailableInBootstrap);
    }
}

final class TestApplication extends Application
{
    public bool $bootstrapped = false;
    public TestResponseEmitter $emitter;

    protected function services(ServiceProviderRegistry $providers): void
    {
        $this->emitter = new TestResponseEmitter();
        $providers->add(new TestApplicationProvider($this->emitter));
    }

    protected function bootstrap(Injector $injector): void
    {
        $this->bootstrapped = true;

        Route::get("/hello/{name}", fn(string $name) => "Hello " . $name);
    }
}

final readonly class TestApplicationProvider implements ServiceProviderInterface
{
    public function __construct(private TestResponseEmitter $emitter)
    {
    }

    public function register(Bindings $bindings): void
    {
        $bindings->bind(TestApplicationMarker::class)
            ->toFactory(fn() => new TestApplicationMarker("ready"))
            ->singleton();

        $bindings->bind(ResponseEmitterInterface::class)
            ->toValue($this->emitter);
    }
}

final readonly class TestApplicationMarker
{
    public function __construct(public string $value)
    {
    }
}

final class TestEnvironmentApplication extends Application
{
    public ?string $configuredName = null;
    public ?string $serviceName = null;

    public function __construct(private readonly string $environmentFile)
    {
        parent::__construct();
    }

    protected function environmentFiles(): array
    {
        return [$this->environmentFile];
    }

    protected function configure(Config $config): void
    {
        $this->configuredName = $config->options(TestEnvironmentOptions::class)->name;
    }

    protected function services(ServiceProviderRegistry $providers): void
    {
        $this->serviceName = $this->getConfig()->options(TestEnvironmentOptions::class)->name;
    }
}

#[Options(prefix: "ATOM_APP_TEST_")]
final readonly class TestEnvironmentOptions
{
    public function __construct(public string $name = "")
    {
    }
}

final readonly class TestOptionsConsumer
{
    public function __construct(public TestEnvironmentOptions $options)
    {
    }
}

final class TestPathApplication extends Application
{
    public ?string $configuredName = null;

    public function __construct(private readonly string $root)
    {
        parent::__construct();
    }

    protected function rootPath(): string
    {
        return $this->root;
    }

    protected function configure(Config $config): void
    {
        $this->configuredName = $config->options(TestEnvironmentOptions::class)->name;
    }
}

final class TestPageApplication extends Application
{
    public bool $pageRouteWasAvailableInBootstrap = false;

    public function __construct(private readonly string $pageDirectory)
    {
        parent::__construct();
    }

    protected function configurePaths(Paths $paths): void
    {
        $paths->alias("pages", $this->pageDirectory);
    }

    protected function pages(PageRegistry $pages): void
    {
        $pages->directory("@pages", "/app");
    }

    protected function bootstrap(Injector $injector): void
    {
        $this->pageRouteWasAvailableInBootstrap = (new RouteMatcher($this->getRouter()))
            ->match("GET", "/app/hello-page")
            ->isFound();
    }
}

final class TestComponentApplication extends Application
{
    public bool $componentWasAvailableInBootstrap = false;

    protected function components(ComponentRegistry $components): void
    {
        $components->register("App.Test", TestApplicationComponent::class);
    }

    protected function bootstrap(Injector $injector): void
    {
        $this->componentWasAvailableInBootstrap = $injector
            ->get(ComponentRegistry::class)
            ->has("App.Test");
    }
}

final class TestApplicationComponent implements ComponentInterface
{
    public function render(): string
    {
        return "";
    }
}

final class TestResponseEmitter implements ResponseEmitterInterface
{
    public ?Response $response = null;

    public function emit(Response $response): void
    {
        $this->response = $response;
    }
}
