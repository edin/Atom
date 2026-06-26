<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Ast\ElementNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\TemplateNode;
use Atom\View\Ast\TextNode;
use Atom\View\Component\ComponentFactoryInterface;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use Atom\View\Component\Fragment;
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

    public function testInterpolatesExpressionsInsideAttributeValues(): void
    {
        $html = $this->render('<a href="/articles/{{ $article->id }}" title="Open {{ $article->title }}">{{ $article->title }}</a>', [
            "article" => (object) [
                "id" => 42,
                "title" => 'Hello "Atom"',
            ],
        ]);

        $this->assertSame('<a href="/articles/42" title="Open Hello &quot;Atom&quot;">Hello &quot;Atom&quot;</a>', $html);
    }

    public function testRendersRegisteredComponentWithAssignedProperties(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("MyButton", TestButtonComponent::class);

        $html = $this->render('<MyButton text="Save" :disabled="$disabled" button-kind="primary" />', [
            "disabled" => true,
        ], $registry);

        $this->assertSame('<button class="primary" disabled>Save</button>', $html);
    }

    public function testCreatesComponentsUsingConfiguredFactory(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("FactoryComponent", TestFactoryRenderedComponent::class);

        $html = (new ViewRenderer(
            components: $registry,
            componentFactory: new TestComponentFactory()
        ))->render((new ViewParser())->parse('<FactoryComponent />'));

        $this->assertSame("factory", $html);
    }

    public function testAssignsOwnedFragmentsToDeclaredFragmentProperties(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Panel", TestPanelComponent::class);

        $html = $this->render(
            '<Panel title="Users"><Panel.Body><p>{{ $name }}</p></Panel.Body></Panel>',
            [],
            $registry
        );

        $this->assertSame('<section><h2>Users</h2><p>Ada</p></section>', $html);
    }

    public function testAssignsAttributeAsFragmentWhenPropertyRequiresFragment(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Panel", TestPanelComponent::class);

        $html = $this->render('<Panel title="Hello" />', [], $registry);

        $this->assertSame('<section><h2>Hello</h2></section>', $html);
    }

    public function testAssignsDefaultContentToDeclaredContentProperty(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Frame", TestFrameComponent::class);

        $html = $this->render('<Frame>Hello {{ $name }}</Frame>', [
            "name" => "Edin",
        ], $registry);

        $this->assertSame('<div>Hello Edin</div>', $html);
    }

    public function testForwardsUnknownAttributesThroughAttributeBag(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("ForwardButton", TestForwardButtonComponent::class);

        $html = $this->render('<ForwardButton id="save" disabled>Save</ForwardButton>', [], $registry);

        $this->assertSame('<button id="save" disabled>Save</button>', $html);
    }

    public function testRendersComponentReturningTemplateNode(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("TemplateReturn", TestTemplateReturnComponent::class);

        $html = $this->render('<TemplateReturn />', ["name" => "Ada"], $registry);

        $this->assertSame('<strong>Ada</strong>', $html);
    }

    public function testRendersComponentReturningViewNode(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("NodeReturn", TestNodeReturnComponent::class);

        $html = $this->render('<NodeReturn />', [], $registry);

        $this->assertSame('<span>Node</span>', $html);
    }

    public function testRendersComponentReturningViewNodeArray(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("ArrayReturn", TestArrayReturnComponent::class);

        $html = $this->render('<ArrayReturn />', [], $registry);

        $this->assertSame('Hello <strong>World</strong>', $html);
    }

    public function testThrowsWhenRequiredComponentPropertyIsMissing(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("RequiredCard", TestRequiredCardComponent::class);

        $this->expectException(ViewRenderException::class);

        $this->render('<RequiredCard />', [], $registry);
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
    private function render(string $source, array $variables = [], ?ComponentRegistry $components = null): string
    {
        return (new ViewRenderer(components: $components ?? new ComponentRegistry()))
            ->render((new ViewParser())->parse($source), $variables);
    }
}

final class TestButtonComponent implements ComponentInterface
{
    public string $text = "";
    public bool $disabled = false;
    public string $buttonKind = "";

    public function render(): string
    {
        return '<button class="' . $this->buttonKind . '"' . ($this->disabled ? " disabled" : "") . ">{$this->text}</button>";
    }
}

final class TestPanelComponent implements ComponentInterface
{
    public ?Fragment $title = null;
    public ?Fragment $body = null;

    public function render(): string
    {
        return "<section><h2>" . $this->title?->render() . "</h2>" . $this->body?->render(["name" => "Ada"]) . "</section>";
    }
}

final class TestFrameComponent implements ComponentInterface
{
    public ?Fragment $content = null;

    public function render(): string
    {
        return "<div>" . $this->content?->render() . "</div>";
    }
}

final class TestRequiredCardComponent implements ComponentInterface
{
    public string $title;

    public function render(): string
    {
        return $this->title;
    }
}

final class TestForwardButtonComponent implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;

    public function render(): string
    {
        return "<button{$this->attributes->render()}>" . $this->content?->render() . "</button>";
    }
}

final class TestFactoryRenderedComponent implements ComponentInterface
{
    public string $value = "";

    public function render(): string
    {
        return $this->value;
    }
}

final class TestTemplateReturnComponent implements ComponentInterface
{
    public function render(): mixed
    {
        return new TemplateNode([
            new ElementNode("strong", children: [
                new ExpressionNode('$name'),
            ]),
        ]);
    }
}

final class TestNodeReturnComponent implements ComponentInterface
{
    public function render(): mixed
    {
        return new ElementNode("span", children: [
            new TextNode("Node"),
        ]);
    }
}

final class TestArrayReturnComponent implements ComponentInterface
{
    public function render(): mixed
    {
        return [
            new TextNode("Hello "),
            new ElementNode("strong", children: [
                new TextNode("World"),
            ]),
        ];
    }
}

final class TestComponentFactory implements ComponentFactoryInterface
{
    public function create(string $className): ComponentInterface
    {
        $component = new $className();
        $component->value = "factory";

        return $component;
    }
}
