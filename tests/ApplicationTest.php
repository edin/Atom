<?php

declare(strict_types=1);

namespace Atom\Tests;

use Atom\Application;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Di\ServiceProviderRegistry;
use Atom\Dispatcher\ResponseEmitterInterface;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Router\Route;
use Atom\Router\RouteMatcher;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
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

final class TestResponseEmitter implements ResponseEmitterInterface
{
    public ?Response $response = null;

    public function emit(Response $response): void
    {
        $this->response = $response;
    }
}
