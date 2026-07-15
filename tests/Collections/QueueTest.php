<?php

declare(strict_types=1);

namespace Atom\Tests\Collections;

use Atom\Collections\Queue;
use PHPUnit\Framework\TestCase;

final class QueueTest extends TestCase
{
    private Queue $queue;

    protected function setUp(): void
    {
        $this->queue = Queue::from([1, 2, 3, 4]);
    }

    public function testInstanceType(): void
    {
        $this->assertInstanceOf(Queue::class, $this->queue);
        $this->assertCount(4, $this->queue);
    }

    public function testEnqueue(): void
    {
        $this->queue->enqueue(5);
        $this->assertCount(5, $this->queue);
    }

    public function testDequeue(): void
    {
        $value = $this->queue->dequeue();
        $this->assertCount(3, $this->queue);
        $this->assertEquals(1, $value);
    }

    public function testPeek(): void
    {
        $value = $this->queue->peek();
        $this->assertCount(4, $this->queue);
        $this->assertEquals(1, $value);
    }
}
