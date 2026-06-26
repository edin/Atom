<?php

namespace Atom\Tests\Collections;

use Atom\Collections\Collection;
use Atom\Collections\ReadOnlyCollection;
use Atom\Collections\Queue;
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

    public function testContainsAnyAndContainsAll(): void
    {
        $this->assertTrue($this->collection->containsAny([0, 3]));
        $this->assertFalse($this->collection->containsAny([0, 6]));
        $this->assertTrue($this->collection->containsAll([1, 3, 5]));
        $this->assertFalse($this->collection->containsAll([1, 6]));
    }

    public function testAtReturnsItemByIndexOrDefault(): void
    {
        $this->assertSame(3, $this->collection->at(2));
        $this->assertSame("missing", $this->collection->at(10, "missing"));
    }

    public function testFilter()
    {
        $collection = $this->collection->filter(function ($x) {
            return $x > 1;
        });
        $this->assertCount(4, $collection);
        $this->assertFalse($collection->contains(1));
    }

    public function testFilterKeepsCollectionListLike(): void
    {
        $collection = ReadOnlyCollection::from([1, 2, 3])->filter(function (int $x): bool {
            return $x > 1;
        });

        $this->assertSame([2, 3], $collection->toArray());
        $this->assertSame(2, $collection->first());
    }

    public function testTransformsKeepConcreteCollectionType(): void
    {
        $queue = Queue::from([1, 2, 3])->filter(function (int $x): bool {
            return $x > 1;
        });

        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertSame(2, $queue->peek());
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
        $collection = $this->collection->reversed();
        $this->assertEquals([5, 4, 3, 2, 1], $collection->toArray());
    }

    public function testConcat()
    {
        $collection = $this->collection->concat([6, 7]);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7], $collection->toArray());
    }

    public function testSliceTakeAndSkip(): void
    {
        $this->assertSame([2, 3], $this->collection->slice(1, 2)->toArray());
        $this->assertSame([1, 2], $this->collection->take(2)->toArray());
        $this->assertSame([4, 5], $this->collection->skip(3)->toArray());
    }

    public function testExcludeRemovesProvidedValuesAndKeepsListLikeIndexes(): void
    {
        $collection = Collection::from([1, 2, 3, 4, 5]);

        $collection->exclude([2, 4]);

        $this->assertSame([1, 3, 5], $collection->toArray());
        $this->assertSame(1, $collection->first());
    }

    public function testImplode()
    {
        $this->assertEquals("1,2,3,4,5", $this->collection->implode(","));
    }

    public function testChunkBy()
    {
        $chunks = $this->collection->chunkBy(2);

        $this->assertCount(3, $chunks);
    }
}
