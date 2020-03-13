<?php

namespace Atom\Tests\Collections;

use Atom\Collections\Interfaces\IQueue;
use Atom\Collections\Queue;
use PHPUnit\Framework\TestCase;

final class QueueTest extends TestCase
{
    private $queue;

    protected function setUp(): void
    {
        $this->queue = Queue::from([1, 2, 3, 4]);
    }

    public function testInstanceType()
    {
        $this->assertInstanceOf(IQueue::class, $this->queue);
        $this->assertInstanceOf(Queue::class, $this->queue);
        $this->assertCount(4, $this->queue);
    }

    public function testEnqueue()
    {
        $this->queue->enqueue(5);
        $this->assertCount(5, $this->queue);
    }

    public function testDequeue()
    {
        $value = $this->queue->dequeue();
        $this->assertCount(3, $this->queue);
        $this->assertEquals(1, $value);
    }

    public function testPeek()
    {
        $value = $this->queue->peek();
        $this->assertCount(4, $this->queue);
        $this->assertEquals(1, $value);
    }
}
