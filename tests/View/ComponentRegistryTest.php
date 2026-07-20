<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use Atom\View\Component\ComponentRegistryException;
use Atom\View\Component\ComponentSet;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ComponentRegistryTest extends TestCase
{
    public function testComponentSetProvidesImmutableValidatedDefinitions(): void
    {
        $base = ComponentSet::from(["Alert" => TestAlertComponent::class]);
        $extended = $base->with("Panel", ComponentSetPanelComponent::class);

        $this->assertSame(["Alert" => TestAlertComponent::class], $base->all());
        $this->assertSame([
            "Alert" => TestAlertComponent::class,
            "Panel" => ComponentSetPanelComponent::class,
        ], $extended->all());
        $this->assertCount(2, $extended);
        $this->assertTrue($extended->has("Panel"));
        $this->assertSame(ComponentSetPanelComponent::class, $extended->get("Panel"));
        $this->assertSame($extended->all(), iterator_to_array($extended));
    }

    public function testComponentSetsMergeWithoutChangingTheirSources(): void
    {
        $first = ComponentSet::from(["Alert" => TestAlertComponent::class]);
        $second = ComponentSet::from(["Panel" => ComponentSetPanelComponent::class]);

        $merged = $first->merge($second);

        $this->assertSame(["Alert" => TestAlertComponent::class], $first->all());
        $this->assertSame(["Panel" => ComponentSetPanelComponent::class], $second->all());
        $this->assertSame([
            "Alert" => TestAlertComponent::class,
            "Panel" => ComponentSetPanelComponent::class,
        ], $merged->all());
    }

    public function testComponentSetRejectsDuplicateMergedDefinitions(): void
    {
        $this->expectException(ComponentRegistryException::class);
        $this->expectExceptionMessage("more than one component set");

        ComponentSet::from(["Alert" => TestAlertComponent::class])->merge(
            ComponentSet::from(["Alert" => TestAlertComponent::class])
        );
    }

    public function testRegistryImportsSetAtomically(): void
    {
        $registry = (new ComponentRegistry())->register("Alert", TestAlertComponent::class);
        $set = ComponentSet::from([
            "Panel" => ComponentSetPanelComponent::class,
            "Alert" => TestAlertComponent::class,
        ]);

        try {
            $registry->import($set);
            $this->fail("Expected component set collision to fail.");
        } catch (ComponentRegistryException $exception) {
            $this->assertStringContainsString("Alert", $exception->getMessage());
        }

        $this->assertFalse($registry->has("Panel"));
        $this->assertSame(["Alert" => TestAlertComponent::class], $registry->all());
    }

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

final class ComponentSetPanelComponent implements ComponentInterface
{
    public function render(): string
    {
        return "";
    }
}
