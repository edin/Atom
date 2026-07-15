<?php

declare(strict_types=1);

namespace Atom\Tests\Collections;

use Atom\Collections\Stack;
use PHPUnit\Framework\TestCase;

final class StackTest extends TestCase
{
    private Stack $stack;

    protected function setUp(): void
    {
        $this->stack = Stack::from([1, 2, 3, 4]);
    }

    public function testInstanceType(): void
    {
        $this->assertInstanceOf(Stack::class, $this->stack);
        $this->assertCount(4, $this->stack);
    }

    public function testPush(): void
    {
        $this->stack->push(5);
        $this->assertCount(5, $this->stack);
    }

    public function testPop(): void
    {
        $value = $this->stack->pop();
        $this->assertCount(3, $this->stack);
        $this->assertEquals(4, $value);
    }

    public function testPeek(): void
    {
        $value = $this->stack->peek();
        $this->assertCount(4, $this->stack);
        $this->assertEquals(4, $value);
    }
}
