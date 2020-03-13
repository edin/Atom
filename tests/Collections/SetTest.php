<?php

namespace Atom\Tests\Collections;

use Atom\Collections\Interfaces\ISet;
use Atom\Collections\Set;
use PHPUnit\Framework\TestCase;

final class SetTest extends TestCase
{
    private $set;

    protected function setUp(): void
    {
        $this->set = Set::from([1, 2]);
    }

    public function testInstanceType()
    {
        $this->assertInstanceOf(ISet::class, $this->set);
        $this->assertInstanceOf(Set::class, $this->set);
        $this->assertCount(2, $this->set);
    }

    public function testUnion()
    {
        $set = $this->set->union([3, 4]);
        $this->assertCount(4, $set);
    }

    public function testUnionOverlaping()
    {
        $set = $this->set->union([2, 3]);
        $this->assertCount(3, $set);
    }

    public function testInteresect()
    {
        $set = $this->set->intersect([2, 3]);
        $this->assertCount(1, $set);
        $this->assertContains(2, $set);
    }
}
