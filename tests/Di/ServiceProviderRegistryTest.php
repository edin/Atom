<?php

declare(strict_types=1);

namespace Atom\Tests\Di;

use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Di\ServiceProviderRegistry;
use PHPUnit\Framework\TestCase;

final class ServiceProviderRegistryTest extends TestCase
{
    public function testRegistersProviderInstancesAndCreatesInjector(): void
    {
        $injector = ServiceProviderRegistry::create()
            ->add(new ExampleServiceProvider())
            ->injector();

        $this->assertInstanceOf(ExampleService::class, $injector->get(ExampleService::class));
    }

    public function testRegistersProviderClasses(): void
    {
        $bindings = new ServiceProviderRegistry([ExampleServiceProvider::class])
            ->bindings();

        $this->assertArrayHasKey(ExampleService::class, $bindings->providers());
    }
}

final class ExampleServiceProvider implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(ExampleService::class)->toSelf()->singleton();
    }
}

final class ExampleService
{
}
