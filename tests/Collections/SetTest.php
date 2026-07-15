<?php

declare(strict_types=1);

namespace Atom\Tests\Collections;

use Atom\Collections\Collection;
use Atom\Collections\ReadOnlyCollection;
use Atom\Collections\Set;
use PHPUnit\Framework\TestCase;

final class SetTest extends TestCase
{
    private Set $set;

    protected function setUp(): void
    {
        $this->set = Set::from([1, 2]);
    }

    public function testInstanceType(): void
    {
        $this->assertInstanceOf(Set::class, $this->set);
        $this->assertInstanceOf(ReadOnlyCollection::class, $this->set);
        $this->assertNotInstanceOf(Collection::class, $this->set);
        $this->assertNotInstanceOf(\ArrayAccess::class, $this->set);
        $this->assertCount(2, $this->set);
    }

    public function testConstructorKeepsOnlyUniqueValues(): void
    {
        $set = Set::from([1, 1, 2, 2, 3]);

        $this->assertSame([1, 2, 3], $set->toArray());
    }

    public function testAddKeepsSetUnique(): void
    {
        $this->set->add(2);
        $this->set->add(3);

        $this->assertSame([1, 2, 3], $this->set->toArray());
    }

    public function testRemoveDeletesValue(): void
    {
        $this->set->remove(1);

        $this->assertSame([2], $this->set->toArray());
    }

    public function testUnion(): void
    {
        $set = $this->set->union([3, 4]);
        $this->assertCount(4, $set);
    }

    public function testUnionOverlapping(): void
    {
        $set = $this->set->union([2, 3]);
        $this->assertCount(3, $set);
        $this->assertSame([1, 2, 3], $set->toArray());
    }

    public function testIntersect(): void
    {
        $set = $this->set->intersect([2, 3]);
        $this->assertCount(1, $set);
        $this->assertContains(2, $set);
    }
}
