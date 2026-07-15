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
use Atom\Http\CookieJar;
use Atom\Http\TrustedProxyMiddleware;
use Atom\Http\CorsMiddleware;
use Atom\Http\RequestIdMiddleware;
use Atom\Http\RequestBodyLimitMiddleware;
use Atom\Http\TrustedHostMiddleware;
use Atom\Http\RateLimitMiddleware;
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
        $this->assertArrayHasKey(CookieJar::class, $bindings->providers());
        $this->assertArrayHasKey(TrustedProxyMiddleware::class, $bindings->providers());
        $this->assertArrayHasKey(CorsMiddleware::class, $bindings->providers());
        $this->assertArrayHasKey(RequestIdMiddleware::class, $bindings->providers());
        $this->assertArrayHasKey(RequestBodyLimitMiddleware::class, $bindings->providers());
        $this->assertArrayHasKey(TrustedHostMiddleware::class, $bindings->providers());
        $this->assertArrayHasKey(RateLimitMiddleware::class, $bindings->providers());
        $this->assertArrayHasKey(Response::class, $bindings->providers());
        $this->assertArrayHasKey(ResponseEmitterInterface::class, $bindings->providers());
        $this->assertArrayHasKey(ResultHandlerRegistry::class, $bindings->providers());
        $this->assertArrayHasKey(ResultConverter::class, $bindings->providers());
        $this->assertArrayHasKey(RouteInvoker::class, $bindings->providers());
        $this->assertArrayHasKey(Dispatcher::class, $bindings->providers());
    }
}
