<?php

declare(strict_types=1);

namespace Atom\Tests;

use Atom\Application;
use Atom\Container;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Di\ServiceProviderRegistry;
use Atom\Router\Route;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContainerTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::$app = null;
        Route::clearRouter();
    }

    public function testResolvesServicesFromCurrentApplication(): void
    {
        $app = new ContainerTestApplication();
        $app->initialize();

        $this->assertTrue(Container::has(ContainerTestMarker::class));
        $this->assertSame("ready", Container::get(ContainerTestMarker::class)->value);
    }

    public function testThrowsWhenApplicationIsNotInitialized(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Application has not been initialized.");

        Container::get(ContainerTestMarker::class);
    }
}

final class ContainerTestApplication extends Application
{
    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(new ContainerTestProvider());
    }

    protected function bootstrap(Injector $injector): void
    {
    }
}

final readonly class ContainerTestProvider implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->value(ContainerTestMarker::class, new ContainerTestMarker("ready"));
    }
}

final readonly class ContainerTestMarker
{
    public function __construct(public string $value)
    {
    }
}
