<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Component\ComponentInterface;
use Atom\View\Component\NewComponentFactory;
use PHPUnit\Framework\TestCase;

final class ComponentFactoryTest extends TestCase
{
    public function testNewComponentFactoryCreatesComponent(): void
    {
        $component = (new NewComponentFactory())->create(TestFactoryComponent::class);

        $this->assertInstanceOf(TestFactoryComponent::class, $component);
    }
}

final class TestFactoryComponent implements ComponentInterface
{
    public function render(): string
    {
        return "";
    }
}
