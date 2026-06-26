<?php

declare(strict_types=1);

namespace Atom\Tests\Collections;

use Atom\Collections\PagedCollection;
use PHPUnit\Framework\TestCase;

final class PagedCollectionTest extends TestCase
{
    public function testFromPageBuildsPaginationMetadata(): void
    {
        $collection = PagedCollection::fromPage([1, 2], 5, 2, 2);

        $this->assertSame([1, 2], $collection->toArray());
        $this->assertSame(5, $collection->getTotalCount());
        $this->assertSame(2, $collection->getPageSize());
        $this->assertSame(2, $collection->getCurrentPage());
        $this->assertSame(3, $collection->getTotalPages());
        $this->assertTrue($collection->hasPrevious());
        $this->assertTrue($collection->hasNext());
    }

    public function testFromPageRequiresPositivePageSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PagedCollection::fromPage([], 0, 1, 0);
    }
}
