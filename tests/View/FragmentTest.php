<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Component\Fragment;
use PHPUnit\Framework\TestCase;

final class FragmentTest extends TestCase
{
    public function testRendersWithVariables(): void
    {
        $fragment = new Fragment(static fn(array $variables = []): string => "Hello " . $variables["name"]);

        $this->assertSame("Hello Ada", $fragment->render(["name" => "Ada"]));
    }

    public function testDetectsEmptyWhitespaceContent(): void
    {
        $fragment = new Fragment(static fn(): string => " \n\t ");

        $this->assertTrue($fragment->isEmpty());
    }

    public function testRenderOrReturnsFallbackWhenContentIsEmpty(): void
    {
        $fragment = new Fragment(static fn(): string => " ");

        $this->assertSame("Fallback", $fragment->renderOr("Fallback"));
    }

    public function testRenderOrReturnsContentWhenContentIsNotEmpty(): void
    {
        $fragment = new Fragment(static fn(array $variables = []): string => "Hello " . $variables["name"]);

        $this->assertSame("Hello Ada", $fragment->renderOr("Fallback", ["name" => "Ada"]));
    }
}
