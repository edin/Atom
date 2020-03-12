<?php

namespace Atom\Tests\Collections;

use Atom\Collections\IReadOnlyCollection;
use Atom\Collections\ReadOnlyCollection;
use PHPUnit\Framework\TestCase;

final class CollectionsTest extends TestCase
{
    private $collection;

    protected function setUp(): void
    {
        $this->collection = ReadOnlyCollection::from([1, 2, 3, 4, 5]);
    }

    public function testInstanceType()
    {
        $this->assertInstanceOf(IReadOnlyCollection::class, $this->collection);
        $this->assertInstanceOf(ReadOnlyCollection::class, $this->collection);
        $this->assertCount(5, $this->collection);
    }

    public function testFirstAndLast()
    {
        $this->assertEquals(1, $this->collection->first());
        $this->assertEquals(5, $this->collection->last());
    }

    public function testContains()
    {
        $this->assertTrue($this->collection->contains(1));
        $this->assertTrue($this->collection->contains(2));
        $this->assertTrue($this->collection->contains(3));
        $this->assertTrue($this->collection->contains(4));
        $this->assertTrue($this->collection->contains(5));

        $this->assertFalse($this->collection->contains(0));
        $this->assertFalse($this->collection->contains(true));
        $this->assertFalse($this->collection->contains(false));
        $this->assertFalse($this->collection->contains(6));
    }

    public function testFilter()
    {
        $collection = $this->collection->filter(function ($x) {
            return $x > 1;
        });
        $this->assertCount(4, $collection);
        $this->assertFalse($collection->contains(1));
    }

    public function testMap()
    {
        $collection = $this->collection->map(function ($x) {
            return $x * 2;
        });

        $this->assertCount(5, $collection);
        $this->assertTrue($collection->contains(2));
        $this->assertTrue($collection->contains(4));
        $this->assertTrue($collection->contains(10));
    }

    public function testFlatMap()
    {
        $collection = $this->collection->flatMap(function ($x) {
            return [$x, -$x];
        });

        $this->assertCount(10, $collection);
        $this->assertTrue($collection->contains(-1));
        $this->assertTrue($collection->contains(1));
        $this->assertTrue($collection->contains(-5));
        $this->assertTrue($collection->contains(5));
    }

    public function testReduce()
    {
        $value = $this->collection->reduce(function ($a, $b) {
            return $a + $b;
        });

        $this->assertEquals(15, $value);
    }

    public function testReverse()
    {
        $collection = $this->collection->reverse();
        $this->assertEquals([5, 4, 3, 2, 1], $collection->toArray());
    }

    public function testConcat()
    {
        $collection = $this->collection->concat([6, 7]);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7], $collection->toArray());
    }
}
