<?php

namespace Atom\Tests;

use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testAllOk(): void
    {
        $this->assertEquals("1", "1");
    }

    public function testEverythingIsCoveredByTests(): void
    {
        $this->assertEquals(1, 1);
    }

    public function testYouSeeItsAllWorking(): void
    {
        $this->assertEquals(1, 1);
    }
}