<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Page\Page;
use Atom\View\Ast\ElementNode;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\Children;
use Atom\View\Component\ComponentHydrator;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Component\FromContext;
use Atom\View\Component\TemplateFragment;
use Atom\View\Parser\ViewParser;
use Atom\View\Render\PhpExpressionEvaluator;
use Atom\View\Render\ViewContext;
use Atom\View\Render\ViewRenderException;
use PHPUnit\Framework\TestCase;

final class ComponentHydratorTest extends TestCase
{
    public function testHydratesPublicPropertiesFromEvaluatedAttributes(): void
    {
        $component = new HydratedCardComponent();
        $node = $this->element('<Card title="Hello" :count="$count" />');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(["count" => 3]),
            static fn(): string => ""
        );

        $this->assertSame("Hello", $component->title);
        $this->assertSame(3, $component->count);
    }

    public function testInterpolatesStringAttributeValues(): void
    {
        $component = new HydratedCardComponent();
        $node = $this->element('<Card title="Hello {{ $name }}" />');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(["name" => "Ada"]),
            static fn(): string => ""
        );

        $this->assertSame("Hello Ada", $component->title);
    }

    public function testConvertsScalarAttributeToFragmentWhenPropertyRequiresFragment(): void
    {
        $component = new HydratedCardComponent();
        $node = $this->element('<Card fragment-title="Hello <b>World</b>" />');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(),
            static fn(): string => ""
        );

        $this->assertSame('Hello &lt;b&gt;World&lt;/b&gt;', $component->fragmentTitle?->render());
    }

    public function testConvertsBoundAttributeValueToFragmentWhenPropertyRequiresFragment(): void
    {
        $component = new HydratedCardComponent();
        $node = $this->element('<Card :fragment-title="$title" />');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(["title" => "Hello"]),
            static fn(): string => ""
        );

        $this->assertSame("Hello", $component->fragmentTitle?->render());
    }

    public function testHydratesNamedAndContentFragments(): void
    {
        $component = new HydratedCardComponent();
        $node = $this->element('<Card><Card.Body>Body</Card.Body>Content</Card>');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(),
            static fn(array $nodes, ViewContext $context): string => count($nodes) === 1 ? "one" : "many"
        );

        $this->assertSame("one", $component->body?->render());
        $this->assertSame("one", $component->content?->render());
    }

    public function testHydratesTemplateFragmentContentWithNodesAndSource(): void
    {
        $component = new TemplateFragmentContentComponent();
        $node = $this->element('<Card><Button variant="danger">Delete</Button></Card>');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(),
            static fn(array $nodes, ViewContext $context): string => "preview"
        );

        $this->assertSame("preview", $component->content?->render());
        $this->assertCount(1, $component->content?->nodes());
        $this->assertSame('<Button variant="danger">Delete</Button>', $component->content?->source());
    }

    public function testHydratesNamedTemplateFragments(): void
    {
        $component = new TemplateFragmentContentComponent();
        $node = $this->element('<Card><Card.Body><strong>Body</strong></Card.Body></Card>');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(),
            static fn(array $nodes, ViewContext $context): string => "body"
        );

        $this->assertSame("body", $component->body?->render());
        $this->assertSame("<strong>Body</strong>", $component->body?->source());
    }

    public function testCollectsDeclaredChildComponentsAndRemovesThemFromContent(): void
    {
        $component = new HydratedTableComponent();
        $node = $this->element('<Table><Column name="Title" />Tail</Table>');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(),
            static fn(array $nodes, ViewContext $context): string => count($nodes) === 1 ? "tail" : "unexpected"
        );

        $this->assertCount(1, $component->columns);
        $this->assertInstanceOf(HydratedColumnComponent::class, $component->columns[0]);
        $this->assertSame("Title", $component->columns[0]->name);
        $this->assertSame("tail", $component->content?->render());
    }

    public function testThrowsWhenCollectedChildComponentTypeIsInvalid(): void
    {
        $this->expectException(ViewRenderException::class);
        $this->expectExceptionMessage("mapped by Atom\Tests\View\InvalidChildTypeComponent::\$columns");

        $this->hydrator()->hydrate(
            new InvalidChildTypeComponent(),
            $this->element('<Table><Column /></Table>'),
            new ViewContext(),
            static fn(): string => ""
        );
    }

    public function testNormalizesAttributeNamesToPropertyNames(): void
    {
        $component = new HydratedCardComponent();
        $node = $this->element('<Card user-name="Ada" is_active />');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(),
            static fn(): string => ""
        );

        $this->assertSame("Ada", $component->userName);
        $this->assertTrue($component->isActive);
    }

    public function testNormalizesFragmentNamesToPropertyNames(): void
    {
        $component = new HydratedCardComponent();
        $node = $this->element('<Card><Card.EmptyState>Empty</Card.EmptyState></Card>');

        $this->hydrator()->hydrate(
            $component,
            $node,
            new ViewContext(),
            static fn(array $nodes, ViewContext $context): string => "empty"
        );

        $this->assertSame("empty", $component->emptyState?->render());
    }

    public function testThrowsWhenRequiredPropertyIsMissing(): void
    {
        $this->expectException(ViewRenderException::class);

        $this->hydrator()->hydrate(
            new RequiredComponent(),
            $this->element('<Card />'),
            new ViewContext(),
            static fn(): string => ""
        );
    }

    public function testThrowsWhenRequiredFragmentIsMissing(): void
    {
        $this->expectException(ViewRenderException::class);

        $this->hydrator()->hydrate(
            new RequiredFragmentComponent(),
            $this->element('<Card />'),
            new ViewContext(),
            static fn(): string => ""
        );
    }

    public function testPassesWhenRequiredPropertiesAreProvided(): void
    {
        $component = new RequiredFragmentComponent();

        $this->hydrator()->hydrate(
            $component,
            $this->element('<Card><Card.Body>Body</Card.Body></Card>'),
            new ViewContext(),
            static fn(array $nodes, ViewContext $context): string => "body"
        );

        $this->assertSame("body", $component->body->render());
    }

    public function testAssignsUnknownAttributesToAttributeBag(): void
    {
        $component = new AttributeBagComponent();

        $this->hydrator()->hydrate(
            $component,
            $this->element('<Card title="Hello" data-id="42" disabled />'),
            new ViewContext(),
            static fn(): string => ""
        );

        $this->assertSame(["data-id" => "42", "disabled" => true], $component->attributes->all());
    }

    public function testInitializesEmptyAttributeBagWhenNoUnknownAttributesExist(): void
    {
        $component = new AttributeBagComponent();

        $this->hydrator()->hydrate(
            $component,
            $this->element('<Card title="Hello" />'),
            new ViewContext(),
            static fn(): string => ""
        );

        $this->assertSame([], $component->attributes->all());
    }

    public function testThrowsForUnknownAttributeWithoutAttributeBag(): void
    {
        $this->expectException(ViewRenderException::class);
        $this->expectExceptionMessage("Unknown attribute 'unknown' on component Atom\Tests\View\HydratedCardComponent rendered from <Card>");

        $this->hydrator()->hydrate(
            new HydratedCardComponent(),
            $this->element('<Card unknown="value" />'),
            new ViewContext(),
            static fn(): string => ""
        );
    }

    public function testAssignsPageContextToPageAwareComponents(): void
    {
        $page = new HydratedPageContextPage();
        $component = new PageAwareComponent();

        $this->hydrator()->hydrate(
            $component,
            $this->element('<FieldError />'),
            new ViewContext(["page" => $page]),
            static fn(): string => ""
        );

        $this->assertSame($page, $component->page);
    }

    public function testAssignsPropertiesFromContextWhenNotProvidedExplicitly(): void
    {
        $component = new ContextAwareComponent();

        $this->hydrator()->hydrate(
            $component,
            $this->element('<Card />'),
            new ViewContext(["model" => "context-value"]),
            static fn(): string => ""
        );

        $this->assertSame("context-value", $component->model);
    }

    public function testExplicitAttributesOverrideContextProperties(): void
    {
        $component = new ContextAwareComponent();

        $this->hydrator()->hydrate(
            $component,
            $this->element('<Card model="explicit-value" />'),
            new ViewContext(["model" => "context-value"]),
            static fn(): string => ""
        );

        $this->assertSame("explicit-value", $component->model);
    }

    public function testThrowsHelpfulMessageForUnknownNamedFragment(): void
    {
        $this->expectException(ViewRenderException::class);
        $this->expectExceptionMessage("Unknown fragment <Card.Footer> on component Atom\Tests\View\HydratedCardComponent");

        $this->hydrator()->hydrate(
            new HydratedCardComponent(),
            $this->element('<Card><Card.Footer>Footer</Card.Footer></Card>'),
            new ViewContext(),
            static fn(): string => ""
        );
    }

    public function testThrowsHelpfulMessageWhenComponentReceivesContentWithoutContentProperty(): void
    {
        $this->expectException(ViewRenderException::class);
        $this->expectExceptionMessage("received child content starting with text 'Body'");

        $this->hydrator()->hydrate(
            new NoContentComponent(),
            $this->element('<Card>Body</Card>'),
            new ViewContext(),
            static fn(): string => ""
        );
    }

    private function hydrator(): ComponentHydrator
    {
        return new ComponentHydrator(new PhpExpressionEvaluator());
    }

    private function element(string $source): ElementNode
    {
        $node = (new ViewParser())->parse($source)->children[0];

        $this->assertInstanceOf(ElementNode::class, $node);

        return $node;
    }
}

final class HydratedCardComponent implements ComponentInterface
{
    public string $title = "";
    public int $count = 0;
    public string $userName = "";
    public bool $isActive = false;
    public ?Fragment $body = null;
    public ?Fragment $emptyState = null;
    public ?Fragment $fragmentTitle = null;
    public ?Fragment $content = null;

    public function render(): string
    {
        return "";
    }
}

final class HydratedTableComponent implements ComponentInterface
{
    /** @var HydratedColumnComponent[] */
    #[Children("Column", HydratedColumnComponent::class)]
    public array $columns = [];
    public ?Fragment $content = null;

    public function render(): string
    {
        return "";
    }
}

final class TemplateFragmentContentComponent implements ComponentInterface
{
    public ?TemplateFragment $body = null;
    public ?TemplateFragment $content = null;

    public function render(): string
    {
        return "";
    }
}

final class HydratedColumnComponent implements ComponentInterface
{
    public string $name = "";

    public function render(): string
    {
        return "";
    }
}

final class InvalidChildTypeComponent implements ComponentInterface
{
    #[Children("Column", \stdClass::class)]
    public array $columns = [];

    public function render(): string
    {
        return "";
    }
}

final class NoContentComponent implements ComponentInterface
{
    public function render(): string
    {
        return "";
    }
}

final class RequiredComponent implements ComponentInterface
{
    public string $title;
    public ?string $subtitle = null;
    public bool $visible = true;

    public function render(): string
    {
        return "";
    }
}

final class RequiredFragmentComponent implements ComponentInterface
{
    public Fragment $body;

    public function render(): string
    {
        return "";
    }
}

final class AttributeBagComponent implements ComponentInterface
{
    public string $title = "";
    public AttributeBag $attributes;

    public function render(): string
    {
        return "";
    }
}

final class HydratedPageContextPage extends Page
{
}

final class PageAwareComponent implements ComponentInterface
{
    public Page $page;

    public function render(): string
    {
        return "";
    }
}

final class ContextAwareComponent implements ComponentInterface
{
    #[FromContext("model")]
    public ?string $model = null;

    public function render(): string
    {
        return "";
    }
}
