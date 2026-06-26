<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use Atom\View\Component\ComponentRegistryException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ComponentRegistryTest extends TestCase
{
    public function testRegistersAndResolvesComponentClass(): void
    {
        $registry = new ComponentRegistry();

        $this->assertSame($registry, $registry->register("Alert", TestAlertComponent::class));

        $this->assertTrue($registry->has("Alert"));
        $this->assertSame(TestAlertComponent::class, $registry->get("Alert"));
        $this->assertSame(["Alert" => TestAlertComponent::class], $registry->all());
    }

    public function testThrowsForUnknownComponent(): void
    {
        $this->expectException(ComponentRegistryException::class);

        (new ComponentRegistry())->get("Missing");
    }

    public function testThrowsForDuplicateComponent(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Alert", TestAlertComponent::class);

        $this->expectException(ComponentRegistryException::class);

        $registry->register("Alert", TestAlertComponent::class);
    }

    public function testThrowsWhenComponentClassDoesNotImplementInterface(): void
    {
        $this->expectException(ComponentRegistryException::class);

        (new ComponentRegistry())->register("Alert", stdClass::class);
    }
}

final class TestAlertComponent implements ComponentInterface
{
    public function render(): string
    {
        return "";
    }
}
