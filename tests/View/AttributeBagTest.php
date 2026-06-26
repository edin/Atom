<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Component\AttributeBag;
use PHPUnit\Framework\TestCase;

final class AttributeBagTest extends TestCase
{
    public function testStoresAndRendersAttributes(): void
    {
        $bag = new AttributeBag([
            "id" => "save",
            "disabled" => true,
            "hidden" => false,
            "title" => 'Save "now"',
        ]);

        $this->assertTrue($bag->has("id"));
        $this->assertSame("save", $bag->get("id"));
        $this->assertSame("fallback", $bag->get("missing", "fallback"));
        $this->assertSame([
            "id" => "save",
            "disabled" => true,
            "hidden" => false,
            "title" => 'Save "now"',
        ], $bag->all());
        $this->assertSame(' id="save" disabled title="Save &quot;now&quot;"', $bag->render());
    }
}
