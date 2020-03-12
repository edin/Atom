<?php

namespace Atom\Tests\Collections;

use Atom\Collections\IReadOnlyCollection;
use Atom\Collections\ReadOnlyCollection;
use PHPUnit\Framework\TestCase;

final class CollectionsTest extends TestCase
{
    public function testReadOnlyCollection()
    {
        $collection = ReadOnlyCollection::from([1,2,3,4,5]);

        $this->assertInstanceOf(IReadOnlyCollection::class, $collection);
        $this->assertInstanceOf(ReadOnlyCollection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertEquals(1, $collection->first());
        $this->assertEquals(5, $collection->last());

        $this->assertTrue($collection->contains(1));
        $this->assertTrue($collection->contains(2));
        $this->assertTrue($collection->contains(3));
        $this->assertTrue($collection->contains(4));
        $this->assertTrue($collection->contains(5));

        $this->assertFalse($collection->contains(0));
        $this->assertFalse($collection->contains(true));
        $this->assertFalse($collection->contains(false));
        $this->assertFalse($collection->contains(6));
    }

    public function testFilter()
    {
        $collection = ReadOnlyCollection::from([1,2,3,4,5]);

        $collection = $collection->filter(function ($x) {
            return $x > 1;
        });

        $this->assertCount(4, $collection);
        $this->assertFalse($collection->contains(1));
    }


    public function testMap()
    {
        $collection = ReadOnlyCollection::from([1,2,3,4,5]);

        $collection = $collection->map(function ($x) {
            return $x * 2;
        });

        $this->assertCount(5, $collection);
        $this->assertTrue($collection->contains(2));
        $this->assertTrue($collection->contains(4));
        $this->assertTrue($collection->contains(10));
    }

    public function testFlatMap()
    {
        $collection = ReadOnlyCollection::from([1,2,3,4,5]);

        $collection = $collection->flatMap(function ($x) {
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
        $collection = ReadOnlyCollection::from([1,2,3,4,5]);

        $value = $collection->reduce(function ($a, $b) {
            return $a + $b;
        });

        $this->assertEquals(15, $value);
    }
}
