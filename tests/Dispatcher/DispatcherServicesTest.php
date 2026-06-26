<?php

declare(strict_types=1);

namespace Atom\Tests\Dispatcher;

use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;
use Atom\Dispatcher\Dispatcher;
use Atom\Dispatcher\DispatcherServices;
use Atom\Dispatcher\ResponseEmitterInterface;
use Atom\Dispatcher\ResultConverter;
use Atom\Dispatcher\ResultHandlerRegistry;
use Atom\Dispatcher\RouteInvoker;
use Atom\Di\Injector;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Router\Router;
use PHPUnit\Framework\TestCase;

final class DispatcherServicesTest extends TestCase
{
    public function testDispatcherServicesRegistersNewDiBindings(): void
    {
        $bindings = Bindings::create();
        $provider = new DispatcherServices();

        $provider->register($bindings);

        $this->assertInstanceOf(ServiceProviderInterface::class, $provider);
        $this->assertArrayHasKey(Injector::class, $bindings->providers());
        $this->assertArrayHasKey(Router::class, $bindings->providers());
        $this->assertArrayHasKey(Request::class, $bindings->providers());
        $this->assertArrayHasKey(Response::class, $bindings->providers());
        $this->assertArrayHasKey(ResponseEmitterInterface::class, $bindings->providers());
        $this->assertArrayHasKey(ResultHandlerRegistry::class, $bindings->providers());
        $this->assertArrayHasKey(ResultConverter::class, $bindings->providers());
        $this->assertArrayHasKey(RouteInvoker::class, $bindings->providers());
        $this->assertArrayHasKey(Dispatcher::class, $bindings->providers());
    }
}
