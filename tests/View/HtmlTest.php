<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Html;
use PHPUnit\Framework\TestCase;

final class HtmlTest extends TestCase
{
    public function testEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame("&lt;Atom &amp; &#039;PHP&#039;&gt;", Html::escape("<Atom & 'PHP'>"));
    }
}
