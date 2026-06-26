<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Parser\ViewParser;
use Atom\View\Render\ViewRenderException;
use Atom\View\Render\ViewRenderer;
use PHPUnit\Framework\TestCase;

final class ViewRendererTest extends TestCase
{
    public function testRendersTextElementsAndEscapedExpressions(): void
    {
        $html = $this->render('<h1>Hello {{ $name }}</h1>', [
            "name" => '<Edin>',
        ]);

        $this->assertSame('<h1>Hello &lt;Edin&gt;</h1>', $html);
    }

    public function testRendersIfElseIfAndElse(): void
    {
        $template = '@if($count > 10)<strong>Many</strong>@elseif($count > 0)<span>Some</span>@else<em>None</em>@endif';

        $this->assertSame('<strong>Many</strong>', $this->render($template, ["count" => 11]));
        $this->assertSame('<span>Some</span>', $this->render($template, ["count" => 2]));
        $this->assertSame('<em>None</em>', $this->render($template, ["count" => 0]));
    }

    public function testRendersForEachWithScopedVariables(): void
    {
        $html = $this->render('@foreach($users as $id => $user)<span>{{ $id }}:{{ $user["name"] }}</span>@endforeach', [
            "users" => [
                10 => ["name" => "Ada"],
                20 => ["name" => "Linus"],
            ],
        ]);

        $this->assertSame('<span>10:Ada</span><span>20:Linus</span>', $html);
    }

    public function testRendersStaticBoundBooleanAndSpreadAttributes(): void
    {
        $html = $this->render('<button class="btn" :disabled="$disabled" :data-count="$count" {{ $attrs }}>Save</button>', [
            "disabled" => true,
            "count" => 3,
            "attrs" => [
                "title" => 'Save "now"',
                "hidden" => false,
            ],
        ]);

        $this->assertSame('<button class="btn" disabled data-count="3" title="Save &quot;now&quot;">Save</button>', $html);
    }

    public function testThrowsWhenForEachSourceIsNotIterable(): void
    {
        $this->expectException(ViewRenderException::class);

        $this->render('@foreach($users as $user)<span>{{ $user }}</span>@endforeach', [
            "users" => "nope",
        ]);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function render(string $source, array $variables = []): string
    {
        return (new ViewRenderer())->render((new ViewParser())->parse($source), $variables);
    }
}
