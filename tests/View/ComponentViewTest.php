<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Modules\Framework\Components\FieldError;
use Atom\Modules\Framework\Components\Alert;
use Atom\Modules\Framework\Components\AppShell;
use Atom\Modules\Framework\Components\Badge;
use Atom\Modules\Framework\Components\Breadcrumb;
use Atom\Modules\Framework\Components\Breadcrumbs;
use Atom\Modules\Framework\Components\Button;
use Atom\Modules\Framework\Components\Card;
use Atom\Modules\Framework\Components\CheckField;
use Atom\Modules\Framework\Components\Column;
use Atom\Modules\Framework\Components\Dialog;
use Atom\Modules\Framework\Components\EmptyState;
use Atom\Modules\Framework\Components\Field;
use Atom\Modules\Framework\Components\Form;
use Atom\Modules\Framework\Components\HiddenField;
use Atom\Modules\Framework\Components\Panel;
use Atom\Modules\Framework\Components\PageHeader;
use Atom\Modules\Framework\Components\Pagination;
use Atom\Modules\Framework\Components\SelectField;
use Atom\Modules\Framework\Components\Sidebar;
use Atom\Modules\Framework\Components\SidebarGroup;
use Atom\Modules\Framework\Components\SidebarItem;
use Atom\Modules\Framework\Components\FormActions;
use Atom\Modules\Framework\Components\Inline;
use Atom\Modules\Framework\Components\Icon;
use Atom\Modules\Framework\Components\ListComponent;
use Atom\Modules\Framework\Components\ListItem;
use Atom\Modules\Framework\Components\Stack;
use Atom\Modules\Framework\Components\Stats;
use Atom\Modules\Framework\Components\Tab;
use Atom\Modules\Framework\Components\Tabs;
use Atom\Modules\Framework\Components\Table;
use Atom\Modules\Framework\Components\TextArea;
use Atom\Modules\Framework\Components\TextAreaField;
use Atom\Modules\Framework\Components\TextField;
use Atom\Modules\Framework\Components\TextInput;
use Atom\Modules\Framework\Components\Toolbar;
use Atom\Modules\Framework\Components\ValidationSummary;
use Atom\Page\Page;
use Atom\Support\Paths;
use Atom\Validation\Rules\Required;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use Atom\View\Component\ComponentView;
use Atom\View\Component\Fragment;
use Atom\View\Component\InjectorComponentFactory;
use Atom\View\Parser\ViewParser;
use Atom\View\Render\ViewRenderer;
use PHPUnit\Framework\TestCase;

final class ComponentViewTest extends TestCase
{
    public function testRendersTemplateNextToComponentClass(): void
    {
        $component = new TemplateBackedComponent();
        $component->title = 'Hello <Atom>';
        $component->content = new Fragment(static fn(array $variables = []): string => "Content for " . $variables["name"]);

        $html = $component->render();

        $this->assertSame("<section><h1>Hello &lt;Atom&gt;</h1>Content for Ada</section>\n", $html);
    }

    public function testTemplateContextProvidesHelpers(): void
    {
        $component = new ContextBackedComponent();
        $component->title = 'Hello <Atom>';
        $component->content = new Fragment(static fn(): string => "Body");

        $html = $component->render();

        $this->assertSame('<article class="card is-active" data-id="42"><h1>Hello &lt;Atom&gt;</h1>Body</article>' . "\n", $html);
    }

    public function testFrameworkValidationComponentsRenderPageErrors(): void
    {
        $page = new ValidationComponentPage();
        $page->title = "";
        $page->validate();

        $registry = new ComponentRegistry();
        $registry->register("FieldError", FieldError::class);
        $registry->register("ValidationSummary", ValidationSummary::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<FieldError name="title" /><ValidationSummary />'),
            ["page" => $page]
        );

        $this->assertStringContainsString('<p id="title-error" class="atom-field-error">The field is required.</p>', $html);
        $this->assertStringContainsString('<div class="atom-validation-summary"><ul><li>The field is required.</li></ul></div>', $html);
    }

    public function testFrameworkValidationComponentsRenderNothingWithoutErrors(): void
    {
        $page = new ValidationComponentPage();

        $registry = new ComponentRegistry();
        $registry->register("FieldError", FieldError::class);
        $registry->register("ValidationSummary", ValidationSummary::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<FieldError name="title" /><ValidationSummary />'),
            ["page" => $page]
        );

        $this->assertSame("", $html);
    }

    public function testFrameworkInputComponentsBindPageValuesAndValidationState(): void
    {
        $page = new ValidationComponentPage();
        $page->title = "";
        $page->body = "Hello <Atom>";
        $page->validate();

        $registry = new ComponentRegistry();
        $registry->register("TextInput", TextInput::class);
        $registry->register("TextArea", TextArea::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<TextInput name="title" maxlength="120" /><TextArea name="body" rows="5" />'),
            ["page" => $page]
        );

        $this->assertStringContainsString(
            '<input type="text" id="title" name="title" class="atom-input is-invalid" aria-invalid="true" aria-describedby="title-error" maxlength="120">',
            $html
        );
        $this->assertStringContainsString(
            '<textarea id="body" name="body" class="atom-textarea" rows="5">Hello &lt;Atom&gt;</textarea>',
            $html
        );
    }

    public function testFrameworkInputComponentsRenderWithoutExtraAttributes(): void
    {
        $page = new ValidationComponentPage();
        $page->title = "Atom";
        $page->body = "Body";

        $registry = new ComponentRegistry();
        $registry->register("TextInput", TextInput::class);
        $registry->register("TextArea", TextArea::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<TextInput name="title" /><TextArea name="body" />'),
            ["page" => $page]
        );

        $this->assertStringContainsString('<input type="text" id="title" name="title" value="Atom" class="atom-input">', $html);
        $this->assertStringContainsString('<textarea id="body" name="body" class="atom-textarea">Body</textarea>', $html);
    }

    public function testFrameworkButtonComponentRendersButtonAndLink(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Button variant="danger" class="compact" atom:action="delete(12)">Delete</Button>' .
                '<Button href="/articles" variant="ghost">Articles</Button>' .
                '<Button variant="link-danger" atom:action="askDelete(12)">Delete link</Button>'
            )
        );

        $this->assertStringContainsString(
            '<button type="button" class="atom-button compact" data-variant="danger" atom:action="delete(12)">Delete</button>',
            $html
        );
        $this->assertStringContainsString(
            '<a href="/articles" class="atom-button" data-variant="ghost">Articles</a>',
            $html
        );
        $this->assertStringContainsString(
            '<button type="button" class="atom-button" data-variant="link-danger" atom:action="askDelete(12)">Delete link</button>',
            $html
        );
    }

    public function testFrameworkAlertComponentRendersEscapedContent(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Alert", Alert::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Alert variant="danger" text="Careful &lt;Atom&gt;" />')
        );

        $this->assertSame(
            '<div class="atom-alert" data-variant="danger" data-appearance="soft" role="status">Careful &amp;lt;Atom&amp;gt;</div>',
            $html
        );
    }

    public function testFrameworkAlertComponentRendersTitleDescriptionAndActions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Alert", Alert::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Alert title="Saved" description="Article was published." variant="success" appearance="outline" size="lg">' .
                '<Alert.Actions><Button size="sm">Undo</Button></Alert.Actions>' .
                '</Alert>'
            )
        );

        $this->assertStringContainsString(
            '<div class="atom-alert" data-variant="success" data-appearance="outline" data-size="lg" role="status">',
            $html
        );
        $this->assertStringContainsString('<strong class="atom-alert__title">Saved</strong>', $html);
        $this->assertStringContainsString('<p class="atom-alert__description">Article was published.</p>', $html);
        $this->assertStringContainsString('<div class="atom-alert__actions"><button type="button" class="atom-button" data-size="sm">Undo</button></div>', $html);
    }

    public function testFrameworkBadgeComponentRendersVariantAndContent(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Badge", Badge::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Badge variant="danger" class="compact">Draft</Badge>')
        );

        $this->assertSame(
            '<span class="atom-badge compact" data-variant="danger" data-appearance="soft">Draft</span>',
            $html
        );
    }

    public function testFrameworkPanelComponentRendersTitleAndBody(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Panel", Panel::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Panel title="Stats"><p>Hello</p></Panel>')
        );

        $this->assertStringContainsString('<section class="atom-panel">', $html);
        $this->assertStringContainsString('<header class="atom-panel__header"><div class="atom-panel__main"><h2 class="atom-panel__title">Stats</h2></div></header>', $html);
        $this->assertStringContainsString('<div class="atom-panel__body"><p>Hello</p></div>', $html);
    }

    public function testFrameworkTableCollectsColumnChildren(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Badge", Badge::class);
        $registry->register("Table", Table::class);
        $registry->register("Column", Column::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Table :items="$users" as="user">' .
                '<Column label="Name" field="name" />' .
                '<Column label="Role"><Badge>{{ $user["role"] }}</Badge></Column>' .
                '</Table>'
            ),
            ["users" => [
                ["name" => "Ada", "role" => "Admin"],
                ["name" => "Linus", "role" => "User"],
            ]]
        );

        $this->assertStringContainsString('<th class="atom-table__heading">Name</th>', $html);
        $this->assertStringContainsString('<td class="atom-table__cell">Ada</td>', $html);
        $this->assertStringContainsString('<td class="atom-table__cell"><span class="atom-badge" data-variant="primary" data-appearance="soft">Admin</span></td>', $html);
        $this->assertStringContainsString('<td class="atom-table__cell">Linus</td>', $html);
    }

    public function testFrameworkLayoutComponentsRenderContentAndOptions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Stack", Stack::class);
        $registry->register("Inline", Inline::class);
        $registry->register("Toolbar", Toolbar::class);
        $registry->register("FormActions", FormActions::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Stack gap="sm"><p>A</p></Stack>' .
                '<Inline gap="lg" align="center" justify="between"><span>B</span></Inline>' .
                '<Toolbar gap="sm" justify="end"><button>Filter</button></Toolbar>' .
                '<FormActions align="end"><button>Save</button></FormActions>'
            )
        );

        $this->assertStringContainsString('<div class="atom-stack" data-gap="sm"><p>A</p></div>', $html);
        $this->assertStringContainsString(
            '<div class="atom-inline" data-gap="lg" data-align="center" data-justify="between"><span>B</span></div>',
            $html
        );
        $this->assertStringContainsString(
            '<div class="atom-toolbar" data-gap="sm" data-align="center" data-justify="end"><button>Filter</button></div>',
            $html
        );
        $this->assertStringContainsString(
            '<div class="atom-form-actions" data-align="end"><button>Save</button></div>',
            $html
        );
    }

    public function testFrameworkAppShellAndSidebarRenderNavigationSlots(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("AppShell", AppShell::class);
        $registry->register("Sidebar", Sidebar::class);
        $registry->register("SidebarGroup", SidebarGroup::class);
        $registry->register("SidebarItem", SidebarItem::class);
        $registry->register("Icon", Icon::class);
        $registry->register("Badge", Badge::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<AppShell title="Dashboard">' .
                '<AppShell.Sidebar>' .
                '<Sidebar brand="Atom Admin" href="/">' .
                '<SidebarGroup label="Content">' .
                '<SidebarItem href="/articles" icon="fa-solid fa-file" active>Articles</SidebarItem>' .
                '</SidebarGroup>' .
                '<Sidebar.Footer><Badge variant="success">Online</Badge></Sidebar.Footer>' .
                '</Sidebar>' .
                '</AppShell.Sidebar>' .
                '<p>Main</p>' .
                '</AppShell>'
            )
        );

        $this->assertStringContainsString('<div class="atom-app-shell">', $html);
        $this->assertStringContainsString('<aside class="atom-app-shell__sidebar"><section class="atom-sidebar">', $html);
        $this->assertStringContainsString('<a href="/" class="atom-sidebar__brand-link">Atom Admin</a>', $html);
        $this->assertStringContainsString('<div class="atom-sidebar-group__label">Content</div>', $html);
        $this->assertStringContainsString('<a href="/articles" class="atom-sidebar-item is-active" aria-current="page">', $html);
        $this->assertStringContainsString('<span class="atom-sidebar-item__icon"><span class="atom-icon"><i class="fa-solid fa-file" aria-hidden="true"></i></span></span>', $html);
        $this->assertStringContainsString('<footer class="atom-sidebar__footer"><span class="atom-badge" data-variant="success" data-appearance="soft">Online</span></footer>', $html);
        $this->assertStringContainsString('<header class="atom-app-shell__header"><h1 class="atom-app-shell__title">Dashboard</h1></header>', $html);
        $this->assertStringContainsString('<main class="atom-app-shell__main"><p>Main</p></main>', $html);
    }

    public function testFrameworkPageHeaderAndEmptyStateRenderNamedActions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("PageHeader", PageHeader::class);
        $registry->register("EmptyState", EmptyState::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<PageHeader title="Articles" description="Manage blog content">' .
                '<PageHeader.Actions><Button>New article</Button></PageHeader.Actions>' .
                '</PageHeader>' .
                '<EmptyState title="No articles" description="Create the first article.">' .
                '<EmptyState.Actions><Button>New article</Button></EmptyState.Actions>' .
                '</EmptyState>'
            )
        );

        $this->assertStringContainsString('<header class="atom-page-header">', $html);
        $this->assertStringContainsString('<h1 class="atom-page-header__title">Articles</h1>', $html);
        $this->assertStringContainsString('<div class="atom-page-header__actions"><button type="button" class="atom-button">New article</button></div>', $html);
        $this->assertStringContainsString('<section class="atom-empty-state">', $html);
        $this->assertStringContainsString('<h2 class="atom-empty-state__title">No articles</h2>', $html);
        $this->assertStringContainsString('<div class="atom-empty-state__actions"><button type="button" class="atom-button">New article</button></div>', $html);
    }

    public function testFrameworkPanelAndCardRenderSurfaceFragments(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Panel", Panel::class);
        $registry->register("Card", Card::class);
        $registry->register("Button", Button::class);
        $registry->register("Table", Table::class);
        $registry->register("Column", Column::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Panel title="Article settings" description="Publishing options">' .
                '<Panel.Actions><Button size="sm">Preview</Button></Panel.Actions>' .
                '<p>Panel body</p>' .
                '<Panel.Footer><Button>Save</Button></Panel.Footer>' .
                '</Panel>' .
                '<Panel title="Recent" padding="none"><Table :items="$items"><Column label="Title" field="title" /></Table></Panel>' .
                '<Card title="Drafts" description="12 waiting" href="/drafts">' .
                '<p>Card body</p>' .
                '<Card.Actions><Button size="sm">Open</Button></Card.Actions>' .
                '</Card>'
            ),
            ["items" => [["title" => "Building Atom"]]]
        );

        $this->assertStringContainsString('<section class="atom-panel">', $html);
        $this->assertStringContainsString('<p class="atom-panel__description">Publishing options</p>', $html);
        $this->assertStringContainsString('<div class="atom-panel__actions"><button type="button" class="atom-button" data-size="sm">Preview</button></div>', $html);
        $this->assertStringContainsString('<footer class="atom-panel__footer"><button type="button" class="atom-button">Save</button></footer>', $html);
        $this->assertStringContainsString('<section class="atom-panel" data-padding="none">', $html);
        $this->assertStringContainsString('<div class="atom-panel__body"><div class="atom-table-wrap">', $html);
        $this->assertStringContainsString('<article class="atom-card">', $html);
        $this->assertStringContainsString('<h3 class="atom-card__title"><a href="/drafts" class="atom-card__link">Drafts</a></h3>', $html);
        $this->assertStringContainsString('<footer class="atom-card__actions"><button type="button" class="atom-button" data-size="sm">Open</button></footer>', $html);
    }

    public function testFrameworkStatsRenderValueTrendHrefAndIcon(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Stats", Stats::class);
        $registry->register("Icon", Icon::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                "<Stats label=\"Published\" value=\"48\" description=\"Live articles\" trend=\"+4 this week\" href=\"/articles\">\n" .
                "    <Stats.Icon><Icon variant=\"success\">A</Icon></Stats.Icon>\n" .
                "</Stats>"
            )
        );

        $this->assertStringContainsString('<a href="/articles" class="atom-stats">', $html);
        $this->assertStringContainsString('<div class="atom-stats__label">Published</div>', $html);
        $this->assertStringContainsString('<div class="atom-stats__value">48</div>', $html);
        $this->assertStringContainsString('<span class="atom-stats__description">Live articles</span>', $html);
        $this->assertStringContainsString('<span class="atom-stats__trend">+4 this week</span>', $html);
        $this->assertStringContainsString('<div class="atom-stats__icon"><span class="atom-icon" data-variant="success">A</span></div>', $html);
    }

    public function testFrameworkStatsCanRenderIconAttribute(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Stats", Stats::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Stats label="Views" value="12k" icon="fa-solid fa-chart-line" />')
        );

        $this->assertStringContainsString(
            '<div class="atom-stats__icon"><span class="atom-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span></div>',
            $html
        );
    }

    public function testFrameworkListRendersItemsIconsAndActions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("List", ListComponent::class);
        $registry->register("ListItem", ListItem::class);
        $registry->register("Icon", Icon::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<List>' .
                '<ListItem title="Article created" description="Saved as draft" icon="fa-solid fa-file">' .
                '<ListItem.Actions><Button size="sm">Open</Button></ListItem.Actions>' .
                '</ListItem>' .
                '<ListItem title="Comment" href="/comments">' .
                '<ListItem.Icon><Icon variant="secondary">C</Icon></ListItem.Icon>' .
                '</ListItem>' .
                '</List>'
            )
        );

        $this->assertStringContainsString('<ul class="atom-list">', $html);
        $this->assertStringContainsString('<li class="atom-list-item">', $html);
        $this->assertStringContainsString('<div class="atom-list-item__icon"><span class="atom-icon"><i class="fa-solid fa-file" aria-hidden="true"></i></span></div>', $html);
        $this->assertStringContainsString('<div class="atom-list-item__title">Article created</div>', $html);
        $this->assertStringContainsString('<div class="atom-list-item__description">Saved as draft</div>', $html);
        $this->assertStringContainsString('<div class="atom-list-item__actions"><button type="button" class="atom-button" data-size="sm">Open</button></div>', $html);
        $this->assertStringContainsString('<div class="atom-list-item__title"><a href="/comments" class="atom-list-item__link">Comment</a></div>', $html);
        $this->assertStringContainsString('<div class="atom-list-item__icon"><span class="atom-icon" data-variant="secondary">C</span></div>', $html);
    }

    public function testFrameworkListCanRenderItemsFromSource(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("List", ListComponent::class);
        $registry->register("ListItem", ListItem::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<List :items="$articles" as="article">' .
                '<ListItem :title="$article[\'title\']" :description="$article[\'status\']" />' .
                '</List>'
            ),
            ["articles" => [
                ["title" => "Building Atom", "status" => "Draft"],
                ["title" => "Component showcase", "status" => "Published"],
            ]]
        );

        $this->assertSame(2, substr_count($html, '<li class="atom-list-item">'));
        $this->assertStringContainsString('<div class="atom-list-item__title">Building Atom</div>', $html);
        $this->assertStringContainsString('<div class="atom-list-item__description">Published</div>', $html);
    }

    public function testFrameworkIconRendersContentAndOptions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Icon", Icon::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Icon variant="warning" size="sm">!</Icon>')
        );

        $this->assertSame('<span class="atom-icon" data-variant="warning" data-size="sm">!</span>', $html);
    }

    public function testFrameworkIconRendersPublicSourceAsImage(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Icon", Icon::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Icon src="/icons/article.svg" />')
        );

        $this->assertSame('<span class="atom-icon"><img src="/icons/article.svg" alt=""></span>', $html);
    }

    public function testFrameworkIconRendersFontIconClass(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Icon", Icon::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Icon icon="fa-solid fa-file" />')
        );

        $this->assertSame('<span class="atom-icon"><i class="fa-solid fa-file" aria-hidden="true"></i></span>', $html);
    }

    public function testFrameworkIconFactoryDetectsSourcesAndFontClasses(): void
    {
        $source = Icon::from("@app/Resources/icons/article.svg");
        $font = Icon::from("fa-solid fa-file");

        $this->assertSame("@app/Resources/icons/article.svg", $source->src);
        $this->assertSame("", $source->icon);
        $this->assertSame("", $font->src);
        $this->assertSame("fa-solid fa-file", $font->icon);
    }

    public function testFrameworkIconInlinesSvgSourceResolvedFromPaths(): void
    {
        $root = str_replace("\\", "/", dirname(__DIR__, 2));
        $registry = new ComponentRegistry();
        $registry->register("Icon", Icon::class);
        $bindings = Bindings::create();
        $bindings->value(Paths::class, new Paths($root));
        $injector = new Injector($bindings);

        $html = (new ViewRenderer(
            components: $registry,
            componentFactory: new InjectorComponentFactory($injector)
        ))->render((new ViewParser())->parse('<Icon src="@root/tests/View/ComponentFixtures/article.svg" />'));

        $this->assertStringContainsString('<span class="atom-icon"><svg viewBox="0 0 16 16" aria-hidden="true">', $html);
        $this->assertStringContainsString('<path d="M3 2h7l3 3v9H3z" />', $html);
    }

    public function testFrameworkDialogRendersWhenShownWithNamedActions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Dialog", Dialog::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Dialog :show="$show" size="lg" title="Delete article?" description="This cannot be undone.">' .
                '<p>Delete the draft article.</p>' .
                '<Dialog.Actions><Button variant="ghost">Cancel</Button><Button variant="danger">Delete</Button></Dialog.Actions>' .
                '</Dialog>'
            ),
            ["show" => true]
        );

        $this->assertStringContainsString('<div class="atom-dialog-backdrop">', $html);
        $this->assertStringContainsString('<section class="atom-dialog" role="dialog" aria-modal="true"', $html);
        $this->assertStringContainsString('aria-describedby="dialog-', $html);
        $this->assertStringContainsString('data-size="lg"', $html);
        $this->assertStringContainsString('<h2 id="dialog-', $html);
        $this->assertStringContainsString('class="atom-dialog__title">Delete article?</h2>', $html);
        $this->assertStringContainsString('<footer class="atom-dialog__actions"><button type="button" class="atom-button" data-variant="ghost">Cancel</button><button type="button" class="atom-button" data-variant="danger">Delete</button></footer>', $html);

        $closed = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Dialog :show="$show" title="Closed">Hidden</Dialog>'),
            ["show" => false]
        );

        $this->assertSame("", $closed);
    }

    public function testFrameworkDialogCanRenderHeaderCloseAction(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Dialog", Dialog::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Dialog show title="Saved" closable closeAction="closeDialog">Stored.</Dialog>')
        );

        $this->assertStringContainsString(
            '<button type="button" class="atom-dialog__close" aria-label="Close dialog" atom:action="closeDialog">&times;</button>',
            $html
        );
    }

    public function testFrameworkNavigationComponentsRenderItems(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Breadcrumbs", Breadcrumbs::class);
        $registry->register("Breadcrumb", Breadcrumb::class);
        $registry->register("Tabs", Tabs::class);
        $registry->register("Tab", Tab::class);
        $registry->register("Pagination", Pagination::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Breadcrumbs><Breadcrumb href="/">Home</Breadcrumb><Breadcrumb>Articles</Breadcrumb></Breadcrumbs>' .
                '<Tabs active="drafts"><Tab name="articles" label="Articles" href="/articles">Article content</Tab><Tab name="drafts" label="Drafts" href="/drafts">Draft content</Tab></Tabs>' .
                '<Pagination page="3" total="8" href="/articles?page={page}" navigate preserve-state />' .
                '<Pagination page="2" total="4" action="setPage({page})" />'
            )
        );

        $this->assertStringContainsString('<nav class="atom-breadcrumbs" aria-label="Breadcrumb">', $html);
        $this->assertStringContainsString('<li class="atom-breadcrumbs__item"><a href="/" class="atom-breadcrumb">Home</a></li>', $html);
        $this->assertStringContainsString('<li class="atom-breadcrumbs__item"><span class="atom-breadcrumb">Articles</span></li>', $html);
        $this->assertStringContainsString('<nav class="atom-tabs" aria-label="Tabs">', $html);
        $this->assertStringContainsString('<a href="/articles" class="atom-tab" data-tab="articles">Articles</a>', $html);
        $this->assertStringContainsString('<a href="/drafts" class="atom-tab is-active" aria-current="page" data-tab="drafts">Drafts</a>', $html);
        $this->assertStringContainsString('<div class="atom-tabs__panel">Draft content</div>', $html);
        $this->assertStringNotContainsString('<div class="atom-tabs__panel">Article content</div>', $html);
        $this->assertStringContainsString('<nav class="atom-pagination" aria-label="Pagination">', $html);
        $this->assertStringContainsString('<a class="atom-pagination__item is-active" aria-current="page" href="/articles?page=3" atom:navigate atom:preserve-state>3</a>', $html);
        $this->assertStringContainsString('<a class="atom-pagination__item" href="/articles?page=4" atom:navigate atom:preserve-state>Next</a>', $html);
        $this->assertStringContainsString('<button class="atom-pagination__item is-active" aria-current="page" type="button" atom:action="setPage(2)">2</button>', $html);
        $this->assertStringContainsString('<button class="atom-pagination__item" type="button" atom:action="setPage(3)">Next</button>', $html);
    }

    public function testFrameworkFormAndFieldComponentsRenderStructure(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Form", Form::class);
        $registry->register("Field", Field::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Form submit="save" class="compact">' .
                '<Field label="Title" name="title"><input id="title"></Field>' .
                '</Form>'
            )
        );

        $this->assertSame(
            '<form method="post" atom:submit="save" class="atom-form compact">' .
            '<label class="atom-field" for="title"><span class="atom-field-label">Title</span><input id="title" /></label>' .
            '</form>',
            $html
        );
    }

    public function testFrameworkCompositeFieldComponentsRenderInputsAndErrors(): void
    {
        $page = new ValidationComponentPage();
        $page->title = "";
        $page->body = "Hello <Atom>";
        $page->validate();

        $registry = new ComponentRegistry();
        $registry->register("TextField", TextField::class);
        $registry->register("TextAreaField", TextAreaField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<TextField label="Title" name="title" maxlength="120" />' .
                '<TextAreaField label="Body" name="body" rows="4" />'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString(
            '<label class="atom-field" for="title"><span class="atom-field-label">Title</span><input type="text" id="title" name="title" class="atom-input is-invalid" aria-invalid="true" aria-describedby="title-error" maxlength="120"><p id="title-error" class="atom-field-error">The field is required.</p></label>',
            $html
        );
        $this->assertStringContainsString(
            '<label class="atom-field" for="body"><span class="atom-field-label">Body</span><textarea id="body" name="body" class="atom-textarea" rows="4">Hello &lt;Atom&gt;</textarea></label>',
            $html
        );
    }

    public function testFrameworkSelectFieldRendersOptionsFromObjects(): void
    {
        $page = new ValidationComponentPage();
        $page->categoryId = 2;
        $page->categories = [
            (object) ["id" => 1, "name" => "News"],
            (object) ["id" => 2, "name" => "Updates"],
        ];

        $registry = new ComponentRegistry();
        $registry->register("SelectField", SelectField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<SelectField label="Category" name="category_id" bind="categoryId" optionValue="id" optionText="name" :options="$page->categories" />'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString(
            '<label class="atom-field" for="category_id"><span class="atom-field-label">Category</span><select id="category_id" name="category_id" class="atom-select">',
            $html
        );
        $this->assertStringContainsString('<option value="1">News</option>', $html);
        $this->assertStringContainsString('<option value="2" selected>Updates</option>', $html);
    }

    public function testFrameworkCheckFieldRendersBooleanState(): void
    {
        $page = new ValidationComponentPage();
        $page->published = true;

        $registry = new ComponentRegistry();
        $registry->register("CheckField", CheckField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<CheckField label="Published" name="published" />'),
            ["page" => $page]
        );

        $this->assertSame(
            '<label class="atom-field atom-check-field" for="published">' .
            '<input type="hidden" name="published" value="0">' .
            '<input type="checkbox" id="published" name="published" value="1" checked class="atom-checkbox">' .
            '<span class="atom-field-label">Published</span></label>',
            $html
        );
    }

    public function testFrameworkCheckFieldReadsModelContextAndRendersErrors(): void
    {
        $page = new ValidationComponentPage();
        $page->edit = (object) ["published" => false];
        $page->validate();

        $registry = new ComponentRegistry();
        $registry->register("Form", Form::class);
        $registry->register("CheckField", CheckField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Form :model="$page->edit"><CheckField label="Published" name="published" bind="title" /></Form>'),
            ["page" => $page]
        );

        $this->assertStringContainsString('<input type="checkbox" id="published" name="published" value="1" class="atom-checkbox is-invalid" aria-invalid="true" aria-describedby="published-error">', $html);
        $this->assertStringContainsString('<p id="published-error" class="atom-field-error">The field is required.</p>', $html);
    }

    public function testFrameworkHiddenFieldBindsPageAndModelValues(): void
    {
        $page = new ValidationComponentPage();
        $page->id = 42;
        $page->edit = (object) ["token" => "abc"];

        $registry = new ComponentRegistry();
        $registry->register("Form", Form::class);
        $registry->register("HiddenField", HiddenField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<HiddenField name="id" />' .
                '<Form :model="$page->edit"><HiddenField name="token" /></Form>'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString('<input type="hidden" name="id" value="42">', $html);
        $this->assertStringContainsString('<input type="hidden" name="token" value="abc">', $html);
    }

    public function testFrameworkFormProvidesModelContextToFields(): void
    {
        $page = new ValidationComponentPage();
        $page->edit = (object) [
            "title" => "Model title",
            "body" => "Model body",
        ];

        $registry = new ComponentRegistry();
        $registry->register("Form", Form::class);
        $registry->register("TextField", TextField::class);
        $registry->register("TextAreaField", TextAreaField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Form :model="$page->edit">' .
                '<TextField label="Title" name="title" />' .
                '<TextAreaField label="Body" name="body" />' .
                '</Form>'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString('name="title" value="Model title" class="atom-input"', $html);
        $this->assertStringContainsString('<textarea id="body" name="body" class="atom-textarea">Model body</textarea>', $html);
    }

    public function testFrameworkFormCssDefinesControlStates(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Framework/Resources/css/form.css");

        $this->assertIsString($css);
        $this->assertStringContainsString(".atom-field-label", $css);
        $this->assertStringContainsString(".atom-input:focus", $css);
        $this->assertStringContainsString('.atom-input[aria-invalid="true"]', $css);
        $this->assertStringContainsString(".atom-input:disabled", $css);
        $this->assertStringContainsString(".atom-checkbox", $css);
        $this->assertStringContainsString(".atom-field-error", $css);
        $this->assertStringContainsString(".atom-validation-summary", $css);
    }

    public function testFrameworkLayoutCssKeepsToolbarControlsInline(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Framework/Resources/css/layout.css");

        $this->assertIsString($css);
        $this->assertStringContainsString(".atom-toolbar > .atom-input", $css);
        $this->assertStringContainsString("width: auto", $css);
        $this->assertStringContainsString(".atom-toolbar > .atom-button", $css);
    }

    public function testFrameworkNavigationCssKeepsLinkTabsUnadorned(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Framework/Resources/css/navigation.css");

        $this->assertIsString($css);
        $this->assertStringContainsString("a.atom-tab:hover", $css);
        $this->assertStringContainsString("text-decoration: none", $css);
    }
}

final class TemplateBackedComponent implements ComponentInterface
{
    public string $title = "";

    public ?Fragment $content = null;

    public function render(): string
    {
        return ComponentView::render($this, "ComponentFixtures/TemplateBackedComponent.atom.php");
    }
}

final class ContextBackedComponent implements ComponentInterface
{
    public string $title = "";

    public ?Fragment $content = null;

    public function render(): string
    {
        return ComponentView::render($this, "ComponentFixtures/ContextBackedComponent.atom.php");
    }
}

final class ValidationComponentPage extends Page
{
    #[Required]
    public string $title = "";

    public string $body = "";

    public int $categoryId = 0;

    public array $categories = [];

    public int $id = 0;

    public bool $published = false;

    public object $edit;
}
