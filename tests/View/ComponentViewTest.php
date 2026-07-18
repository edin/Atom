<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Collections\PagedCollection;
use Atom\Modules\Components\FieldError;
use Atom\Modules\Components\Alert;
use Atom\Modules\Components\AppShell;
use Atom\Modules\Components\Avatar;
use Atom\Modules\Components\Badge;
use Atom\Modules\Components\Breadcrumb;
use Atom\Modules\Components\Breadcrumbs;
use Atom\Modules\Components\Button;
use Atom\Modules\Components\ButtonGroup;
use Atom\Modules\Components\Card;
use Atom\Modules\Components\CheckField;
use Atom\Modules\Components\Column;
use Atom\Modules\Components\ControlGroup;
use Atom\Modules\Components\Dialog;
use Atom\Modules\Components\Details;
use Atom\Modules\Components\Divider;
use Atom\Modules\Components\DialogModel;
use Atom\Modules\Components\EmptyState;
use Atom\Modules\Components\Field;
use Atom\Modules\Components\Form;
use Atom\Modules\Components\HiddenField;
use Atom\Modules\Components\FormRow;
use Atom\Modules\Components\FormSection;
use Atom\Modules\Components\Panel;
use Atom\Modules\Components\PageHeader;
use Atom\Modules\Components\Pagination;
use Atom\Modules\Components\Progress;
use Atom\Modules\Components\RadioField;
use Atom\Modules\Components\SelectField;
use Atom\Modules\Components\Skeleton;
use Atom\Modules\Components\Spinner;
use Atom\Modules\Components\Sidebar;
use Atom\Modules\Components\SidebarGroup;
use Atom\Modules\Components\SidebarItem;
use Atom\Modules\Components\SidePanelModel;
use Atom\Modules\Components\SnackBar;
use Atom\Modules\Components\SplitView;
use Atom\Modules\Components\FormActions;
use Atom\Modules\Components\Inline;
use Atom\Modules\Components\Icon;
use Atom\Modules\Components\Kbd;
use Atom\Modules\Components\ListComponent;
use Atom\Modules\Components\ListItem;
use Atom\Modules\Components\Stack;
use Atom\Modules\Components\Stats;
use Atom\Modules\Components\StatusDot;
use Atom\Modules\Components\SwitchField;
use Atom\Modules\Components\Tab;
use Atom\Modules\Components\Tabs;
use Atom\Modules\Components\TabsModel;
use Atom\Modules\Components\Table;
use Atom\Modules\Components\Tag;
use Atom\Modules\Components\TextArea;
use Atom\Modules\Components\TextAreaField;
use Atom\Modules\Components\TextField;
use Atom\Modules\Components\TextInput;
use Atom\Modules\Components\Toast;
use Atom\Modules\Components\ToastModel;
use Atom\Modules\Components\Toolbar;
use Atom\Modules\Components\ValidationSummary;
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

    public function testComponentsValidationComponentsRenderPageErrors(): void
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

    public function testComponentsValidationComponentsRenderNothingWithoutErrors(): void
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

    public function testComponentsValidationSummaryCanFilterFieldsAndRenderTitle(): void
    {
        $page = new ValidationSummaryComponentPage();
        $page->validate();

        $registry = new ComponentRegistry();
        $registry->register("ValidationSummary", ValidationSummary::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<ValidationSummary title="Fix this form" only="title" />'),
            ["page" => $page]
        );

        $this->assertStringContainsString('<p class="atom-validation-summary__title">Fix this form</p>', $html);
        $this->assertStringContainsString('<li>Title is required.</li>', $html);
        $this->assertStringNotContainsString('<li>Summary is required.</li>', $html);

        $empty = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<ValidationSummary only="missing" />'),
            ["page" => $page]
        );

        $this->assertSame("", $empty);
    }

    public function testComponentsInputComponentsBindPageValuesAndValidationState(): void
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

    public function testComponentsInputComponentsRenderWithoutExtraAttributes(): void
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

    public function testComponentsButtonComponentRendersButtonAndLink(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Button variant="primary">Primary</Button>' .
                '<Button variant="danger" class="compact" atom:action="delete(12)">Delete</Button>' .
                '<Button variant="success">Success</Button>' .
                '<Button variant="warning">Warning</Button>' .
                '<Button variant="info">Info</Button>' .
                '<Button shape="square" aria-label="Edit">E</Button>' .
                '<Button icon="fa-solid fa-plus" icon-right="fa-solid fa-arrow-right">Next</Button>' .
                '<Button disabled>Disabled</Button>' .
                '<Button loading variant="primary" icon="fa-solid fa-save">Saving</Button>' .
                '<Button href="/articles" disabled atom:navigate>Disabled link</Button>' .
                '<Button href="/articles" variant="ghost">Articles</Button>' .
                '<Button variant="link-danger" atom:action="askDelete(12)">Delete link</Button>'
            )
        );

        $this->assertStringContainsString('<button type="button" class="atom-button" data-variant="primary">Primary</button>', $html);
        $this->assertStringContainsString(
            '<button type="button" class="atom-button compact" data-variant="danger" atom:action="delete(12)">Delete</button>',
            $html
        );
        $this->assertStringContainsString('<button type="button" class="atom-button" data-variant="success">Success</button>', $html);
        $this->assertStringContainsString('<button type="button" class="atom-button" data-variant="warning">Warning</button>', $html);
        $this->assertStringContainsString('<button type="button" class="atom-button" data-variant="info">Info</button>', $html);
        $this->assertStringContainsString('<button type="button" class="atom-button" data-shape="square" aria-label="Edit">E</button>', $html);
        $this->assertStringContainsString(
            '<button type="button" class="atom-button"><span class="atom-icon"><i class="fa-solid fa-plus" aria-hidden="true"></i></span>Next<span class="atom-icon"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span></button>',
            $html
        );
        $this->assertStringContainsString('<button type="button" class="atom-button" disabled>Disabled</button>', $html);
        $this->assertStringContainsString(
            '<button type="button" class="atom-button" data-variant="primary" data-loading="true" disabled aria-busy="true"><span class="atom-button__spinner" aria-hidden="true"></span>Saving</button>',
            $html
        );
        $this->assertStringContainsString(
            '<a class="atom-button" aria-disabled="true" tabindex="-1" atom:navigate>Disabled link</a>',
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

    public function testComponentsButtonGroupRendersJoinedButtons(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Button", Button::class);
        $registry->register("ButtonGroup", ButtonGroup::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<ButtonGroup aria-label="View mode">' .
                '<Button icon="fa-solid fa-list">List</Button>' .
                '<Button icon="fa-solid fa-table-cells">Table</Button>' .
                '</ButtonGroup>' .
                '<ButtonGroup orientation="vertical"><Button>Top</Button><Button>Bottom</Button></ButtonGroup>'
            )
        );

        $this->assertStringContainsString('<div class="atom-button-group" role="group" aria-label="View mode">', $html);
        $this->assertStringContainsString('<button type="button" class="atom-button"><span class="atom-icon"><i class="fa-solid fa-list" aria-hidden="true"></i></span>List</button>', $html);
        $this->assertStringContainsString('<button type="button" class="atom-button"><span class="atom-icon"><i class="fa-solid fa-table-cells" aria-hidden="true"></i></span>Table</button>', $html);
        $this->assertStringContainsString('<div class="atom-button-group" data-orientation="vertical" role="group"><button type="button" class="atom-button">Top</button><button type="button" class="atom-button">Bottom</button></div>', $html);
    }

    public function testComponentsAlertComponentRendersEscapedContent(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Alert", Alert::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Alert variant="danger" icon="lucide:triangle-alert" text="Careful &lt;Atom&gt;" />')
        );

        $this->assertStringContainsString(
            '<div class="atom-alert" data-variant="danger" data-appearance="soft" role="status"><span class="atom-alert__icon"><span class="atom-icon" data-variant="danger"><svg',
            $html
        );
        $this->assertStringContainsString(
            '<div class="atom-alert__content"><div class="atom-alert__body">Careful &amp;lt;Atom&amp;gt;</div></div></div>',
            $html
        );
    }

    public function testComponentsAlertComponentRendersTitleDescriptionAndActions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Alert", Alert::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Alert title="Saved" description="Article was published." variant="success" appearance="outline" size="lg" icon="fa-solid fa-check">' .
                '<Alert.Actions><Button size="sm">Undo</Button></Alert.Actions>' .
                '</Alert>'
            )
        );

        $this->assertStringContainsString(
            '<div class="atom-alert" data-variant="success" data-appearance="outline" data-size="lg" role="status">',
            $html
        );
        $this->assertStringContainsString('<span class="atom-alert__icon"><span class="atom-icon" data-variant="success"><i class="fa-solid fa-check" aria-hidden="true"></i></span></span>', $html);
        $this->assertStringContainsString('<strong class="atom-alert__title">Saved</strong>', $html);
        $this->assertStringContainsString('<p class="atom-alert__description">Article was published.</p>', $html);
        $this->assertStringContainsString('<div class="atom-alert__actions"><button type="button" class="atom-button" data-size="sm">Undo</button></div>', $html);
    }

    public function testComponentsBadgeComponentRendersVariantAndContent(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Badge", Badge::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Badge variant="danger" class="compact">Draft</Badge><Badge variant="success" appearance="soft">Live</Badge>')
        );

        $this->assertStringContainsString(
            '<span class="atom-badge compact" data-variant="danger">Draft</span>',
            $html
        );
        $this->assertStringContainsString(
            '<span class="atom-badge" data-variant="success" data-appearance="soft">Live</span>',
            $html
        );
    }

    public function testComponentsBadgeCssUsesCompactDaisyLikeRhythm(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/badge.css");

        $this->assertIsString($css);
        $this->assertStringContainsString("justify-content: center;", $css);
        $this->assertStringContainsString("height: 1.5rem;", $css);
        $this->assertStringContainsString("padding: 0 calc(0.75rem - 1px);", $css);
        $this->assertStringContainsString("font-size: 0.875rem;", $css);
        $this->assertStringContainsString("font-weight: 500;", $css);
        $this->assertStringContainsString('.atom-badge[data-size="xs"]', $css);
        $this->assertStringContainsString('.atom-badge[data-size="xl"]', $css);
        $this->assertStringContainsString('.atom-badge[data-appearance="soft"]', $css);
        $this->assertStringContainsString('.atom-badge[data-appearance="solid"]', $css);
    }

    public function testComponentsPrimitiveComponentsRenderContentAndAccessibleDefaults(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Avatar", Avatar::class);
        $registry->register("Details", Details::class);
        $registry->register("Divider", Divider::class);
        $registry->register("Kbd", Kbd::class);
        $registry->register("StatusDot", StatusDot::class);
        $registry->register("Tag", Tag::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Avatar name="Ada Lovelace" size="lg" />' .
                '<Avatar src="/ada.jpg" name="Ada Lovelace" shape="square" />' .
                '<StatusDot variant="success" label="Online" />' .
                '<StatusDot />' .
                '<Tag variant="primary">Framework</Tag>' .
                '<Kbd size="sm">Ctrl</Kbd>' .
                '<Divider>or</Divider>' .
                '<Details summary="Advanced options" open><p>Optional settings.</p></Details>'
            )
        );

        $this->assertStringContainsString('<span class="atom-avatar" data-size="lg" data-shape="circle" role="img" aria-label="Ada Lovelace"><span class="atom-avatar__initials">AL</span></span>', $html);
        $this->assertStringContainsString('<span class="atom-avatar" data-size="md" data-shape="square"><img class="atom-avatar__image" src="/ada.jpg" alt="Ada Lovelace"></span>', $html);
        $this->assertStringContainsString('<span class="atom-status-dot" data-variant="success" data-size="md" role="img" aria-label="Online"></span>', $html);
        $this->assertStringContainsString('<span class="atom-status-dot" data-variant="neutral" data-size="md" aria-hidden="true"></span>', $html);
        $this->assertStringContainsString('<span class="atom-tag" data-variant="primary" data-size="md">Framework</span>', $html);
        $this->assertStringContainsString('<kbd class="atom-kbd" data-size="sm">Ctrl</kbd>', $html);
        $this->assertStringContainsString('<div class="atom-divider" data-orientation="horizontal" role="separator" aria-orientation="horizontal">or</div>', $html);
        $this->assertStringContainsString('<details class="atom-details" open><summary class="atom-details__summary">Advanced options</summary><div class="atom-details__content"><p>Optional settings.</p></div></details>', $html);
    }

    public function testComponentsPrimitiveCssDefinesVariantsSizesAndFocusState(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/primitives.css");
        $entry = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/atom.css");

        $this->assertIsString($css);
        $this->assertIsString($entry);
        $this->assertStringContainsString('.atom-divider[data-orientation="vertical"]', $css);
        $this->assertStringContainsString('.atom-avatar[data-size="xl"]', $css);
        $this->assertStringContainsString('.atom-status-dot[data-variant="success"]', $css);
        $this->assertStringContainsString('.atom-tag[data-variant="danger"]', $css);
        $this->assertStringContainsString('.atom-kbd[data-size="lg"]', $css);
        $this->assertStringContainsString('.atom-details__summary:focus-visible', $css);
        $this->assertStringContainsString('@import url("./css/primitives.css");', $entry);
    }

    public function testComponentsPanelComponentRendersTitleAndBody(): void
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

    public function testComponentsTableCollectsColumnChildren(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Badge", Badge::class);
        $registry->register("Button", Button::class);
        $registry->register("Table", Table::class);
        $registry->register("Column", Column::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Table :items="$users" as="user" sort="name" direction="asc" sortAction="setSort(\'{sort}\', \'{direction}\')">' .
                '<Column label="Name" field="name" sort />' .
                '<Column label="Role"><Badge>{{ $user["role"] }}</Badge></Column>' .
                '<Column label="Actions"><Column.Actions><Button size="sm" atom:action="edit({{ $row[\'id\'] }})">Edit</Button></Column.Actions></Column>' .
                '</Table>'
            ),
            ["users" => [
                ["id" => 1, "name" => "Ada", "role" => "Admin"],
                ["id" => 2, "name" => "Linus", "role" => "User"],
            ]]
        );

        $this->assertStringContainsString('<th class="atom-table__heading is-sortable is-sorted" aria-sort="ascending"><button type="button" class="atom-table__sort" atom:action="setSort(\'name\', \'desc\')"><span>Name</span><span class="atom-table__sort-indicator" data-direction="asc" aria-hidden="true"></span></button></th>', $html);
        $this->assertStringContainsString('<th class="atom-table__heading atom-table__heading--actions" data-align="end">Actions</th>', $html);
        $this->assertStringContainsString('<td class="atom-table__cell">Ada</td>', $html);
        $this->assertStringContainsString('<td class="atom-table__cell"><span class="atom-badge" data-variant="primary">Admin</span></td>', $html);
        $this->assertStringContainsString('<td class="atom-table__cell atom-table__cell--actions" data-align="end"><div class="atom-table__actions"><button type="button" class="atom-button" data-size="sm" atom:action="edit(1)">Edit</button></div></td>', $html);
        $this->assertStringContainsString('<td class="atom-table__cell">Linus</td>', $html);
    }

    public function testComponentsTableRendersEmptyAndPaginationFragmentsWithSourceContext(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("EmptyState", EmptyState::class);
        $registry->register("Pagination", Pagination::class);
        $registry->register("Table", Table::class);
        $registry->register("Column", Column::class);

        $source = PagedCollection::fromPage([], 10, 2, 5);
        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Table :items="$source">' .
                '<Column label="Name" field="name" />' .
                '<Table.Toolbar><span>Total {{ $total }}</span></Table.Toolbar>' .
                '<Table.Empty><EmptyState title="No users" description="Try another filter." /></Table.Empty>' .
                '<Table.Summary>Showing {{ $from }}-{{ $to }} of {{ $total }} on page {{ $currentPage }} with {{ $pageSize }} per page.</Table.Summary>' .
                '<Table.Pagination><Pagination action="setPage({page})" /></Table.Pagination>' .
                '</Table>'
            ),
            ["source" => $source]
        );

        $this->assertStringContainsString('<div class="atom-table-stack">', $html);
        $this->assertStringContainsString('<div class="atom-table__toolbar"><span>Total 10</span></div>', $html);
        $this->assertStringContainsString('<td class="atom-table__empty" colspan="1"><section class="atom-empty-state">', $html);
        $this->assertStringContainsString('<h2 class="atom-empty-state__title">No users</h2>', $html);
        $this->assertStringContainsString('<div class="atom-table__footer">', $html);
        $this->assertStringContainsString('<div class="atom-table__summary">Showing 6-10 of 10 on page 2 with 5 per page.</div>', $html);
        $this->assertStringContainsString('<button class="atom-pagination__item is-active" aria-current="page" type="button" atom:action="setPage(2)">2</button>', $html);
    }

    public function testComponentsTableHidesSummaryAndPaginationWhenSourceIsEmpty(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("EmptyState", EmptyState::class);
        $registry->register("Pagination", Pagination::class);
        $registry->register("Table", Table::class);
        $registry->register("Column", Column::class);

        $source = PagedCollection::fromPage([], 0, 1, 5);
        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Table :items="$source">' .
                '<Column label="Name" field="name" />' .
                '<Table.Empty><EmptyState title="No users" /></Table.Empty>' .
                '<Table.Summary>Showing {{ $from }}-{{ $to }} of {{ $total }}.</Table.Summary>' .
                '<Table.Pagination><Pagination action="setPage({page})" /></Table.Pagination>' .
                '</Table>'
            ),
            ["source" => $source]
        );

        $this->assertStringContainsString('<th class="atom-table__heading">Name</th>', $html);
        $this->assertStringContainsString('<td class="atom-table__empty" colspan="1"><section class="atom-empty-state">', $html);
        $this->assertStringNotContainsString('<div class="atom-table__footer">', $html);
        $this->assertStringNotContainsString('<div class="atom-table__summary">', $html);
        $this->assertStringNotContainsString('<nav class="atom-pagination"', $html);
    }

    public function testComponentsLayoutComponentsRenderContentAndOptions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Stack", Stack::class);
        $registry->register("Inline", Inline::class);
        $registry->register("Toolbar", Toolbar::class);
        $registry->register("Button", Button::class);
        $registry->register("ButtonGroup", ButtonGroup::class);
        $registry->register("FormActions", FormActions::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Stack gap="sm"><p>A</p></Stack>' .
                '<Inline gap="lg" align="center" justify="between"><span>B</span></Inline>' .
                '<Toolbar gap="sm" justify="end" appearance="flat"><button>Filter</button></Toolbar>' .
                '<Toolbar><Toolbar.Start><input class="atom-input"></Toolbar.Start><Toolbar.End><ButtonGroup><Button>A</Button><Button>B</Button></ButtonGroup></Toolbar.End></Toolbar>' .
                '<FormActions align="end"><button>Save</button></FormActions>'
            )
        );

        $this->assertStringContainsString('<div class="atom-stack" data-gap="sm"><p>A</p></div>', $html);
        $this->assertStringContainsString(
            '<div class="atom-inline" data-gap="lg" data-align="center" data-justify="between"><span>B</span></div>',
            $html
        );
        $this->assertStringContainsString(
            '<div class="atom-toolbar" data-gap="sm" data-align="center" data-justify="end" data-appearance="flat"><button>Filter</button></div>',
            $html
        );
        $this->assertStringContainsString(
            '<div class="atom-toolbar" data-align="center" data-justify="between"><div class="atom-toolbar__section atom-toolbar__section--start"><input class="atom-input" /></div><div class="atom-toolbar__section atom-toolbar__section--end"><div class="atom-button-group" role="group"><button type="button" class="atom-button">A</button><button type="button" class="atom-button">B</button></div></div></div>',
            $html
        );
        $this->assertStringContainsString(
            '<div class="atom-form-actions" data-align="end"><button>Save</button></div>',
            $html
        );
    }

    public function testComponentsControlGroupAlignsControlsWithFieldLabels(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Button", Button::class);
        $registry->register("ControlGroup", ControlGroup::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<ControlGroup><Button>Filter</Button></ControlGroup>' .
                '<ControlGroup label="Actions"><Button>Save</Button></ControlGroup>' .
                '<ControlGroup spacer="0"><Button>Plain</Button></ControlGroup>'
            )
        );

        $this->assertStringContainsString('<div class="atom-control-group"><span class="atom-field-label atom-control-group__label" aria-hidden="true">&nbsp;</span><div class="atom-control-group__controls"><button type="button" class="atom-button">Filter</button></div></div>', $html);
        $this->assertStringContainsString('<div class="atom-control-group"><span class="atom-field-label atom-control-group__label">Actions</span><div class="atom-control-group__controls"><button type="button" class="atom-button">Save</button></div></div>', $html);
        $this->assertStringContainsString('<div class="atom-control-group"><div class="atom-control-group__controls"><button type="button" class="atom-button">Plain</button></div></div>', $html);
    }

    public function testComponentsSplitViewRendersOptionalSidePane(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("SplitView", SplitView::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<SplitView :showSide="$editing" sideWidth="420px">' .
                '<table><tbody><tr><td>Article</td></tr></tbody></table>' .
                '<SplitView.Side><form>Edit</form></SplitView.Side>' .
                '</SplitView>'
            ),
            ["editing" => true]
        );

        $this->assertStringContainsString('<div class="atom-split-view has-side" data-gap="md" style="--atom-split-side-width: 420px;">', $html);
        $this->assertStringContainsString('<div class="atom-split-view__main"><table><tbody><tr><td>Article</td></tr></tbody></table></div>', $html);
        $this->assertStringContainsString('<aside class="atom-split-view__side"><form>Edit</form></aside>', $html);

        $closed = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<SplitView :showSide="$editing">' .
                '<p>Main</p>' .
                '<SplitView.Side><p>Side</p></SplitView.Side>' .
                '</SplitView>'
            ),
            ["editing" => false]
        );

        $this->assertStringContainsString('<div class="atom-split-view" data-gap="md" style="--atom-split-side-width: 380px;">', $closed);
        $this->assertStringContainsString('<div class="atom-split-view__main"><p>Main</p></div>', $closed);
        $this->assertStringNotContainsString('atom-split-view__side', $closed);
    }

    public function testComponentsSplitViewCanBindSidePanelModel(): void
    {
        $model = new SidePanelModel();
        $model->open(12);

        $registry = new ComponentRegistry();
        $registry->register("SplitView", SplitView::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<SplitView :model="$editor">' .
                '<p>Main</p>' .
                '<SplitView.Side><form>Edit {{ $editor->value }}</form></SplitView.Side>' .
                '</SplitView>'
            ),
            ["editor" => $model]
        );

        $this->assertStringContainsString('<div class="atom-split-view has-side" data-gap="md" style="--atom-split-side-width: 380px;">', $html);
        $this->assertStringContainsString('<aside class="atom-split-view__side"><form>Edit 12</form></aside>', $html);

        $model->close();
        $closed = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<SplitView :model="$editor"><p>Main</p><SplitView.Side><p>Side</p></SplitView.Side></SplitView>'
            ),
            ["editor" => $model]
        );

        $this->assertStringContainsString('<div class="atom-split-view" data-gap="md" style="--atom-split-side-width: 380px;">', $closed);
        $this->assertStringNotContainsString('atom-split-view__side', $closed);
    }

    public function testComponentsAppShellAndSidebarRenderNavigationSlots(): void
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
                '<Sidebar brand="Atom Admin" href="/" current="/articles">' .
                '<SidebarGroup label="Content">' .
                '<SidebarItem href="/articles" icon="fa-solid fa-file">Articles</SidebarItem>' .
                '<SidebarItem href="/comments">Comments</SidebarItem>' .
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
        $this->assertStringContainsString('<a href="/comments" class="atom-sidebar-item"><span class="atom-sidebar-item__label">Comments</span></a>', $html);
        $this->assertStringContainsString('<span class="atom-sidebar-item__icon"><span class="atom-icon"><i class="fa-solid fa-file" aria-hidden="true"></i></span></span>', $html);
        $this->assertStringContainsString('<footer class="atom-sidebar__footer"><span class="atom-badge" data-variant="success">Online</span></footer>', $html);
        $this->assertStringContainsString('<header class="atom-app-shell__header"><h1 class="atom-app-shell__title">Dashboard</h1></header>', $html);
        $this->assertStringContainsString('<main class="atom-app-shell__main"><p>Main</p></main>', $html);
    }

    public function testComponentsSidebarCanUseAutoCurrentPathFromContext(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Sidebar", Sidebar::class);
        $registry->register("SidebarGroup", SidebarGroup::class);
        $registry->register("SidebarItem", SidebarItem::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Sidebar current="auto">' .
                '<SidebarGroup><SidebarItem href="/articles">Articles</SidebarItem><SidebarItem href="/comments">Comments</SidebarItem></SidebarGroup>' .
                '</Sidebar>'
            ),
            ["currentPath" => "/comments"]
        );

        $this->assertStringContainsString('<a href="/articles" class="atom-sidebar-item"><span class="atom-sidebar-item__label">Articles</span></a>', $html);
        $this->assertStringContainsString('<a href="/comments" class="atom-sidebar-item is-active" aria-current="page"><span class="atom-sidebar-item__label">Comments</span></a>', $html);
    }

    public function testComponentsSidebarItemCanUsePrefixMatch(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Sidebar", Sidebar::class);
        $registry->register("SidebarItem", SidebarItem::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Sidebar current="/articles/12/edit">' .
                '<SidebarItem href="/articles" match="prefix">Articles</SidebarItem>' .
                '<SidebarItem href="/articles-old" match="prefix">Old articles</SidebarItem>' .
                '<SidebarItem href="/articles/12">Article detail</SidebarItem>' .
                '</Sidebar>'
            )
        );

        $this->assertStringContainsString('<a href="/articles" class="atom-sidebar-item is-active" aria-current="page"><span class="atom-sidebar-item__label">Articles</span></a>', $html);
        $this->assertStringContainsString('<a href="/articles-old" class="atom-sidebar-item"><span class="atom-sidebar-item__label">Old articles</span></a>', $html);
        $this->assertStringContainsString('<a href="/articles/12" class="atom-sidebar-item"><span class="atom-sidebar-item__label">Article detail</span></a>', $html);
    }

    public function testComponentsPageHeaderAndEmptyStateRenderNamedActions(): void
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

    public function testComponentsPanelAndCardRenderSurfaceFragments(): void
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

    public function testComponentsStatsRenderValueTrendHrefAndIcon(): void
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

    public function testComponentsStatsCanRenderIconAttribute(): void
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

    public function testComponentsToastRendersWhenShownWithActions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Toast", Toast::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Toast :show="$show" variant="success" position="bottom-end" title="Saved" description="Article updated.">' .
                '<Toast.Actions><Button size="sm" variant="ghost">Dismiss</Button></Toast.Actions>' .
                '</Toast>'
            ),
            ["show" => true]
        );

        $this->assertStringContainsString('<div class="atom-toast-region" data-position="bottom-end">', $html);
        $this->assertStringContainsString('<div class="atom-toast" data-variant="success" role="status">', $html);
        $this->assertStringContainsString('<strong class="atom-toast__title">Saved</strong>', $html);
        $this->assertStringContainsString('<p class="atom-toast__description">Article updated.</p>', $html);
        $this->assertStringContainsString('<div class="atom-toast__actions"><button type="button" class="atom-button" data-variant="ghost" data-size="sm">Dismiss</button></div>', $html);

        $closed = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Toast :show="$show" title="Closed" />'),
            ["show" => false]
        );

        $this->assertSame("", $closed);
    }

    public function testComponentsToastCanBindPageFlashAutomatically(): void
    {
        $page = new ValidationComponentPage();
        $page->flash("Article was saved.", "success", "Saved");

        $registry = new ComponentRegistry();
        $registry->register("Toast", Toast::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Toast><Toast.Actions><Button size="sm" variant="ghost" atom:action="clearFlash">Dismiss</Button></Toast.Actions></Toast>'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString('<div class="atom-toast" data-variant="success" role="status">', $html);
        $this->assertStringContainsString('<strong class="atom-toast__title">Saved</strong>', $html);
        $this->assertStringContainsString('<p class="atom-toast__description">Article was saved.</p>', $html);
        $this->assertStringContainsString('atom:action="clearFlash"', $html);

        $empty = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Toast />'),
            ["page" => new ValidationComponentPage()]
        );

        $this->assertSame("", $empty);
    }

    public function testComponentsToastCanBindToastModel(): void
    {
        $model = new ToastModel();
        $model->open("Article was saved.", "success", "Saved");

        $registry = new ComponentRegistry();
        $registry->register("Toast", Toast::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Toast :model="$toast"><Toast.Actions><Button size="sm" variant="ghost" atom:action="toast.close()">Dismiss</Button></Toast.Actions></Toast>'
            ),
            ["toast" => $model]
        );

        $this->assertStringContainsString('<div class="atom-toast" data-variant="success" role="status">', $html);
        $this->assertStringContainsString('<strong class="atom-toast__title">Saved</strong>', $html);
        $this->assertStringContainsString('<p class="atom-toast__description">Article was saved.</p>', $html);
        $this->assertStringContainsString('atom:action="toast.close()"', $html);

        $model->close();
        $empty = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Toast :model="$toast" />'),
            ["toast" => $model]
        );

        $this->assertSame("", $empty);
    }

    public function testComponentsSnackBarRendersMessageAndActions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("SnackBar", SnackBar::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<SnackBar :show="$show" variant="danger" position="bottom-end" text="Article deleted.">' .
                '<SnackBar.Actions><Button size="sm" variant="ghost">Undo</Button></SnackBar.Actions>' .
                '</SnackBar>'
            ),
            ["show" => true]
        );

        $this->assertStringContainsString('<div class="atom-snackbar-region" data-position="bottom-end">', $html);
        $this->assertStringContainsString('<div class="atom-snackbar" data-variant="danger" role="status">', $html);
        $this->assertStringContainsString('<div class="atom-snackbar__message">Article deleted.</div>', $html);
        $this->assertStringContainsString('<div class="atom-snackbar__actions"><button type="button" class="atom-button" data-variant="ghost" data-size="sm">Undo</button></div>', $html);

        $closed = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<SnackBar :show="$show" text="Closed" />'),
            ["show" => false]
        );

        $this->assertSame("", $closed);
    }

    public function testComponentsSnackBarCanBindPageFlashAutomatically(): void
    {
        $page = new ValidationComponentPage();
        $page->flash("Article was deleted.", "danger", "Deleted");

        $registry = new ComponentRegistry();
        $registry->register("SnackBar", SnackBar::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<SnackBar><SnackBar.Actions><Button size="sm" variant="ghost" atom:action="clearFlash">Dismiss</Button></SnackBar.Actions></SnackBar>'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString('<div class="atom-snackbar" data-variant="danger" role="status">', $html);
        $this->assertStringContainsString('<div class="atom-snackbar__message">Article was deleted.</div>', $html);
        $this->assertStringContainsString('atom:action="clearFlash"', $html);

        $empty = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<SnackBar />'),
            ["page" => new ValidationComponentPage()]
        );

        $this->assertSame("", $empty);
    }

    public function testComponentsAlertCssDefinesToastAndSnackBarSurfaces(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/alert.css");

        $this->assertIsString($css);
        $this->assertStringContainsString("--atom-alert-color: var(--atom-color-base-content);", $css);
        $this->assertStringContainsString("--atom-alert-bg: var(--atom-color-base-200);", $css);
        $this->assertStringContainsString("--atom-alert-soft-fg: var(--atom-color-text);", $css);
        $this->assertStringContainsString("border-radius: var(--atom-radius-box);", $css);
        $this->assertStringContainsString("justify-content: flex-start;", $css);
        $this->assertStringContainsString(".atom-alert__icon", $css);
        $this->assertStringContainsString("margin-left: auto;", $css);
        $this->assertStringContainsString('.atom-alert[data-variant="success"]', $css);
        $this->assertStringContainsString('.atom-alert[data-appearance="soft"]', $css);
        $this->assertStringContainsString("color-mix(in oklab, var(--atom-alert-color) 10%", $css);
        $this->assertStringContainsString("color: var(--atom-alert-soft-fg);", $css);
        $this->assertStringContainsString('.atom-alert[data-appearance="outline"]', $css);
        $this->assertStringContainsString(".atom-toast-region", $css);
        $this->assertStringContainsString(".atom-toast__actions", $css);
        $this->assertStringContainsString(".atom-snackbar-region", $css);
        $this->assertStringContainsString(".atom-snackbar__actions", $css);
    }

    public function testComponentsButtonCssDefinesSemanticVariants(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/button.css");

        $this->assertIsString($css);
        $this->assertStringContainsString("--atom-button-color: var(--atom-color-base-content);", $css);
        $this->assertStringContainsString("--atom-button-bg: var(--atom-color-base-200);", $css);
        $this->assertStringContainsString("border-radius: var(--atom-radius-field);", $css);
        $this->assertStringContainsString("translate: 0 0.5px;", $css);
        $this->assertStringContainsString("user-select: none;", $css);
        $this->assertStringContainsString("box-shadow: none;", $css);
        $this->assertStringContainsString(".atom-button__spinner", $css);
        $this->assertStringContainsString("@keyframes atom-button-spin", $css);
        $this->assertStringContainsString("text-decoration: none;", $css);
        $this->assertStringContainsString('.atom-button[data-variant="primary"]', $css);
        $this->assertStringContainsString('.atom-button[data-variant="success"]', $css);
        $this->assertStringContainsString('.atom-button[data-variant="warning"]', $css);
        $this->assertStringContainsString('.atom-button[data-variant="info"]', $css);
        $this->assertStringContainsString('.atom-button[data-shape="square"]', $css);
        $this->assertStringContainsString('.atom-button[data-shape="rounded"]', $css);
        $this->assertStringContainsString('.atom-button[data-shape="circle"]', $css);
        $this->assertStringContainsString("border-radius: 9999px;", $css);
        $this->assertStringContainsString(".atom-button-group", $css);
        $this->assertStringContainsString('.atom-button-group[data-orientation="vertical"]', $css);
        $this->assertStringContainsString(".atom-button-group > .atom-button", $css);
    }

    public function testComponentsListRendersItemsIconsAndActions(): void
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

    public function testComponentsListCanRenderItemsFromSource(): void
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

    public function testComponentsIconRendersContentAndOptions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Icon", Icon::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Icon variant="warning" appearance="soft" size="sm">!</Icon>')
        );

        $this->assertSame('<span class="atom-icon" data-variant="warning" data-appearance="soft" data-size="sm">!</span>', $html);
    }

    public function testComponentsIconRendersPublicSourceAsImage(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Icon", Icon::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Icon icon="/icons/article.svg" />')
        );

        $this->assertSame('<span class="atom-icon"><img src="/icons/article.svg" alt=""></span>', $html);
    }

    public function testComponentsIconRendersFontIconClass(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Icon", Icon::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Icon icon="fa-solid fa-file" />')
        );

        $this->assertSame('<span class="atom-icon"><i class="fa-solid fa-file" aria-hidden="true"></i></span>', $html);
    }

    public function testComponentsIconRendersBundledLucideIcon(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Icon", Icon::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Icon icon="lucide:search" />')
        );

        $this->assertStringStartsWith('<span class="atom-icon"><svg', $html);
        $this->assertStringContainsString('class="lucide lucide-search"', $html);
        $this->assertStringContainsString('<path d="m21 21-4.34-4.34" />', $html);
    }

    public function testComponentsIconFactoryUsesSingleIconValue(): void
    {
        $source = Icon::from("@app/Resources/icons/article.svg");
        $font = Icon::from("fa-solid fa-file");
        $lucide = Icon::from("lucide:search");

        $this->assertSame("@app/Resources/icons/article.svg", $source->icon);
        $this->assertSame("fa-solid fa-file", $font->icon);
        $this->assertSame("lucide:search", $lucide->icon);
    }

    public function testComponentsIconCssKeepsDefaultIconsPlainAndFramesOnlySoftAppearance(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/panel.css");

        $this->assertIsString($css);
        $this->assertStringContainsString("gap: 2px;", $css);
        $this->assertStringContainsString("line-height: 1.4;", $css);
        $this->assertStringContainsString("width: 1.1em;", $css);
        $this->assertStringContainsString("background: transparent;", $css);
        $this->assertStringContainsString("color: currentColor;", $css);
        $this->assertStringContainsString('.atom-icon[data-appearance="soft"]', $css);
        $this->assertStringContainsString("width: 44px;", $css);
        $this->assertStringContainsString("background: var(--atom-color-soft);", $css);
        $this->assertStringContainsString("color: var(--atom-color-text);", $css);
        $this->assertStringContainsString(".atom-icon > svg:not([fill])", $css);
        $this->assertStringContainsString(".atom-icon[data-variant=\"primary\"] {\n    color: var(--atom-color-primary-text);\n}", $css);
        $this->assertStringContainsString(".atom-icon[data-variant=\"info\"] {\n    color: var(--atom-color-info-text);\n}", $css);
        $this->assertStringContainsString('.atom-icon[data-appearance="soft"][data-variant="primary"]', $css);
        $this->assertStringContainsString('.atom-icon[data-size="sm"]', $css);
    }

    public function testComponentsIconInlinesSvgSourceResolvedFromPaths(): void
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
        ))->render((new ViewParser())->parse('<Icon icon="@root/tests/View/ComponentFixtures/article.svg" />'));

        $this->assertStringContainsString('<span class="atom-icon"><svg viewBox="0 0 16 16" aria-hidden="true">', $html);
        $this->assertStringContainsString('<path d="M3 2h7l3 3v9H3z" />', $html);
    }

    public function testComponentsDialogRendersWhenShownWithNamedActions(): void
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

    public function testComponentsDialogCanRenderHeaderCloseAction(): void
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

    public function testComponentsDialogCanBindDialogModel(): void
    {
        $model = new DialogModel();
        $model->open(12);

        $registry = new ComponentRegistry();
        $registry->register("Dialog", Dialog::class);
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Dialog :model="$dialog" title="Delete article?" closable closeAction="dialog.close()">' .
                '<p>Selected id: {{ $dialog->value }}</p>' .
                '<Dialog.Actions><Button atom:action="dialog.close()">Cancel</Button></Dialog.Actions>' .
                '</Dialog>'
            ),
            ["dialog" => $model]
        );

        $this->assertStringContainsString('<div class="atom-dialog-backdrop">', $html);
        $this->assertStringContainsString('<h2 id="dialog-', $html);
        $this->assertStringContainsString('<p>Selected id: 12</p>', $html);
        $this->assertStringContainsString('atom:action="dialog.close()"', $html);

        $model->close();
        $empty = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Dialog :model="$dialog" title="Closed">Hidden</Dialog>'),
            ["dialog" => $model]
        );

        $this->assertSame("", $empty);
    }

    public function testComponentsNavigationComponentsRenderItems(): void
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

    public function testComponentsTabsCanBindTabsModelAndGenerateActions(): void
    {
        $model = new TabsModel("source");

        $registry = new ComponentRegistry();
        $registry->register("Tabs", Tabs::class);
        $registry->register("Tab", Tab::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Tabs :model="$tabs" action="tabs.select(\'{name}\')">' .
                '<Tab name="preview" label="Preview">Preview content</Tab>' .
                '<Tab name="source" label="Source">Source content</Tab>' .
                '</Tabs>'
            ),
            ["tabs" => $model]
        );

        $this->assertStringContainsString('<button type="button" class="atom-tab" data-tab="preview" atom:action="tabs.select(\'preview\')">Preview</button>', $html);
        $this->assertStringContainsString('<button type="button" class="atom-tab is-active" aria-current="page" data-tab="source" atom:action="tabs.select(\'source\')">Source</button>', $html);
        $this->assertStringContainsString('<div class="atom-tabs__panel">Source content</div>', $html);
        $this->assertStringNotContainsString('<div class="atom-tabs__panel">Preview content</div>', $html);
    }

    public function testComponentsPaginationCanBindPagedSource(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Pagination", Pagination::class);

        $source = PagedCollection::fromPage([1, 2], 9, 2, 2);
        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Pagination :source="$source" action="setPage({page})" />'),
            ["source" => $source]
        );

        $this->assertStringContainsString('<button class="atom-pagination__item" type="button" atom:action="setPage(1)">Previous</button>', $html);
        $this->assertStringContainsString('<button class="atom-pagination__item is-active" aria-current="page" type="button" atom:action="setPage(2)">2</button>', $html);
        $this->assertStringContainsString('<button class="atom-pagination__item" type="button" atom:action="setPage(5)">5</button>', $html);
        $this->assertStringContainsString('<button class="atom-pagination__item" type="button" atom:action="setPage(3)">Next</button>', $html);
    }

    public function testComponentsFormAndFieldComponentsRenderStructure(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Form", Form::class);
        $registry->register("FormRow", FormRow::class);
        $registry->register("FormSection", FormSection::class);
        $registry->register("Field", Field::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Form submit="save" class="compact">' .
                '<FormSection title="Details" description="Main fields">' .
                '<FormRow columns="2"><Field label="Title" name="title"><input id="title"></Field></FormRow>' .
                '</FormSection>' .
                '</Form>'
            )
        );

        $this->assertStringContainsString('<form method="post" atom:submit="save" class="atom-form compact">', $html);
        $this->assertStringContainsString('<section class="atom-form-section">', $html);
        $this->assertStringContainsString('<h3 class="atom-form-section__title">Details</h3>', $html);
        $this->assertStringContainsString('<p class="atom-form-section__description">Main fields</p>', $html);
        $this->assertStringContainsString('<div class="atom-form-row" data-columns="2" data-gap="md">', $html);
        $this->assertStringContainsString('<label class="atom-field" for="title"><span class="atom-field-label">Title</span><input id="title" /></label>', $html);
    }

    public function testComponentsCompositeFieldComponentsRenderInputsAndErrors(): void
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
                '<TextField label="Title" name="title" help="Use a short title." maxlength="120" />' .
                '<TextAreaField label="Body" name="body" help="Markdown is supported." rows="4" />'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString(
            '<label class="atom-field" for="title"><span class="atom-field-label">Title</span><input type="text" id="title" name="title" class="atom-input is-invalid" aria-invalid="true" aria-describedby="title-help title-error" maxlength="120"><p id="title-help" class="atom-field-help">Use a short title.</p><p id="title-error" class="atom-field-error">The field is required.</p></label>',
            $html
        );
        $this->assertStringContainsString(
            '<label class="atom-field" for="body"><span class="atom-field-label">Body</span><textarea id="body" name="body" class="atom-textarea" aria-describedby="body-help" rows="4">Hello &lt;Atom&gt;</textarea><p id="body-help" class="atom-field-help">Markdown is supported.</p></label>',
            $html
        );
    }

    public function testComponentsSelectFieldRendersOptionsFromObjects(): void
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

    public function testComponentsSelectFieldRendersExplicitEmptyOptionValue(): void
    {
        $page = new ValidationComponentPage();
        $page->status = "";
        $page->statuses = [
            ["value" => "", "text" => "All statuses"],
            ["value" => "Published", "text" => "Published"],
        ];

        $registry = new ComponentRegistry();
        $registry->register("SelectField", SelectField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<SelectField label="Status" name="status" :options="$page->statuses" />'),
            ["page" => $page]
        );

        $this->assertStringContainsString('<option value="" selected>All statuses</option>', $html);
        $this->assertStringContainsString('<option value="Published">Published</option>', $html);
    }

    public function testComponentsCheckFieldRendersBooleanState(): void
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
            '<div class="atom-field"><label class="atom-check-field" for="published">' .
            '<input type="hidden" name="published" value="0">' .
            '<input type="checkbox" id="published" name="published" value="1" checked class="atom-checkbox">' .
            '<span class="atom-field-label">Published</span></label></div>',
            $html
        );
    }

    public function testComponentsCheckFieldRendersHelpText(): void
    {
        $page = new ValidationComponentPage();

        $registry = new ComponentRegistry();
        $registry->register("CheckField", CheckField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<CheckField label="Published" name="published" help="Visible to readers." />'),
            ["page" => $page]
        );

        $this->assertStringContainsString('<div class="atom-field"><label class="atom-check-field" for="published">', $html);
        $this->assertStringContainsString('<p id="published-help" class="atom-field-help">Visible to readers.</p>', $html);
    }

    public function testComponentsCheckFieldReadsModelContextAndRendersErrors(): void
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

    public function testComponentsRadioFieldRendersOptionsAndSelectedModelValue(): void
    {
        $page = new ValidationComponentPage();
        $page->edit = (object) ["visibility" => "team"];
        $page->visibilityOptions = [
            ["value" => "private", "text" => "Private"],
            ["value" => "team", "text" => "Team"],
            ["value" => "public", "text" => "Public"],
        ];

        $registry = new ComponentRegistry();
        $registry->register("Form", Form::class);
        $registry->register("RadioField", RadioField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Form :model="$page->edit"><RadioField label="Visibility" name="visibility" orientation="horizontal" help="Choose an audience." :options="$page->visibilityOptions" /></Form>'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString('<fieldset class="atom-field atom-radio-field" aria-describedby="visibility-help">', $html);
        $this->assertStringContainsString('<legend class="atom-field-label">Visibility</legend>', $html);
        $this->assertStringContainsString('class="atom-radio-field__options" data-orientation="horizontal"', $html);
        $this->assertStringContainsString('id="visibility-1" name="visibility" value="team" checked class="atom-radio"', $html);
        $this->assertStringContainsString('<span>Team</span>', $html);
        $this->assertStringContainsString('<p id="visibility-help" class="atom-field-help">Choose an audience.</p>', $html);
    }

    public function testComponentsSwitchFieldRendersBooleanStateHelpAndHiddenFallback(): void
    {
        $page = new ValidationComponentPage();
        $page->published = true;

        $registry = new ComponentRegistry();
        $registry->register("SwitchField", SwitchField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<SwitchField label="Published" name="published" help="Visible to readers." />'),
            ["page" => $page]
        );

        $this->assertStringContainsString('<input type="hidden" name="published" value="0">', $html);
        $this->assertStringContainsString('type="checkbox" role="switch" id="published" name="published" value="1" checked class="atom-switch"', $html);
        $this->assertStringContainsString('<span class="atom-field-label">Published</span>', $html);
        $this->assertStringContainsString('<p id="published-help" class="atom-field-help">Visible to readers.</p>', $html);
    }

    public function testComponentsLoadingComponentsRenderAccessibleStates(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Progress", Progress::class);
        $registry->register("Spinner", Spinner::class);
        $registry->register("Skeleton", Skeleton::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Progress label="Uploading" value="68" showValue variant="success" size="sm" />' .
                '<Progress label="Waiting" />' .
                '<Spinner label="Loading results" size="lg" />' .
                '<Skeleton width="75%" height="2rem" shape="circle" style="margin: 1rem;" />'
            )
        );

        $this->assertStringContainsString('<span class="atom-progress__label">Uploading</span>', $html);
        $this->assertStringContainsString('<span class="atom-progress__value">68%</span>', $html);
        $this->assertStringContainsString('<progress class="atom-progress" value="68" max="100" data-variant="success" data-size="sm" aria-label="Uploading"></progress>', $html);
        $this->assertStringContainsString('<progress class="atom-progress" max="100" data-variant="primary" data-size="md" aria-label="Waiting"></progress>', $html);
        $this->assertStringContainsString('<span class="atom-spinner" data-variant="primary" data-size="lg" role="status"><span class="atom-visually-hidden">Loading results</span></span>', $html);
        $this->assertStringContainsString('class="atom-skeleton" data-shape="circle" style="--atom-skeleton-width: 75%; --atom-skeleton-height: 2rem; margin: 1rem;" aria-hidden="true"', $html);
    }

    public function testComponentsLoadingAndChoiceCssDefinesStatesAndReducedMotion(): void
    {
        $form = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/form.css");
        $loading = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/loading.css");

        $this->assertIsString($form);
        $this->assertIsString($loading);
        $this->assertStringContainsString(".atom-radio-field", $form);
        $this->assertStringContainsString(".atom-radio::before", $form);
        $this->assertStringContainsString(".atom-radio:checked", $form);
        $this->assertStringContainsString(".atom-checkbox::before", $form);
        $this->assertStringContainsString(".atom-checkbox:checked", $form);
        $this->assertStringContainsString(".atom-switch:checked", $form);
        $this->assertStringContainsString(".atom-progress:not([value])", $loading);
        $this->assertStringContainsString(".atom-spinner", $loading);
        $this->assertStringContainsString(".atom-skeleton", $loading);
        $this->assertStringContainsString("prefers-reduced-motion: reduce", $loading);
    }

    public function testComponentsHiddenFieldBindsPageAndModelValues(): void
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

    public function testComponentsFormProvidesModelContextToFields(): void
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

    public function testComponentsFormCssDefinesControlStates(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/form.css");

        $this->assertIsString($css);
        $this->assertStringContainsString(".atom-form {\n    display: grid;\n    gap: var(--atom-space-4);\n}", $css);
        $this->assertStringNotContainsString("padding: 18px;", $css);
        $this->assertStringContainsString(".atom-field-label", $css);
        $this->assertStringContainsString("gap: 4px;", $css);
        $this->assertStringContainsString("font-size: 0.86rem;", $css);
        $this->assertStringContainsString("line-height: 1.25;", $css);
        $this->assertStringContainsString(".atom-control-group", $css);
        $this->assertStringContainsString(".atom-control-group__label", $css);
        $this->assertStringContainsString(".atom-control-group__controls", $css);
        $this->assertStringContainsString(".atom-field-help", $css);
        $this->assertStringContainsString(".atom-form-section", $css);
        $this->assertStringContainsString(".atom-form-row", $css);
        $this->assertStringContainsString(".atom-input:focus", $css);
        $this->assertStringContainsString("outline: 1px solid var(--atom-color-primary);", $css);
        $this->assertStringContainsString("outline-offset: 0;", $css);
        $this->assertStringNotContainsString("padding: 9px 11px;", $css);
        $this->assertStringContainsString(".atom-select {\n    appearance: none;", $css);
        $this->assertStringContainsString("background-size: 16px 16px;", $css);
        $this->assertStringContainsString(".atom-select:focus", $css);
        $this->assertStringContainsString('.atom-input[aria-invalid="true"]', $css);
        $this->assertStringContainsString(".atom-input:disabled", $css);
        $this->assertStringContainsString(".atom-checkbox", $css);
        $this->assertStringContainsString(".atom-field-error", $css);
        $this->assertStringContainsString(".atom-validation-summary", $css);
        $this->assertStringContainsString(".atom-validation-summary__title", $css);
    }

    public function testComponentsTokensDefineDaisyLikeSemanticPalette(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/tokens.css");

        $this->assertIsString($css);
        $this->assertStringContainsString("--atom-color-base-100: oklch(100% 0 0);", $css);
        $this->assertStringContainsString("--atom-color-primary-content:", $css);
        $this->assertStringContainsString("--atom-color-secondary-content:", $css);
        $this->assertStringContainsString("--atom-color-accent:", $css);
        $this->assertStringContainsString("--atom-color-error:", $css);
        $this->assertStringContainsString("--atom-color-danger: var(--atom-color-error);", $css);
        $this->assertStringContainsString("--atom-color-surface: var(--atom-color-base-100);", $css);
    }

    public function testComponentsLayoutCssKeepsToolbarControlsInline(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/layout.css");

        $this->assertIsString($css);
        $this->assertStringContainsString(".atom-page-header__main", $css);
        $this->assertStringContainsString("gap: 2px;", $css);
        $this->assertStringContainsString(".atom-page-header__actions > .atom-form", $css);
        $this->assertStringContainsString("margin: 0;", $css);
        $this->assertStringContainsString("line-height: 1.4;", $css);
        $this->assertStringContainsString(".atom-toolbar > .atom-input", $css);
        $this->assertStringContainsString(".atom-toolbar__section", $css);
        $this->assertStringContainsString('.atom-toolbar[data-appearance="flat"]', $css);
        $this->assertStringContainsString("border-radius: 0;", $css);
        $this->assertStringContainsString('.atom-toolbar[data-appearance="plain"]', $css);
        $this->assertStringContainsString("background: transparent;", $css);
        $this->assertStringContainsString(".atom-toolbar__section--end", $css);
        $this->assertStringContainsString(".atom-toolbar__section > .atom-input", $css);
        $this->assertStringContainsString(".atom-toolbar__section > .atom-field", $css);
        $this->assertStringContainsString("width: auto", $css);
        $this->assertStringContainsString(".atom-toolbar > .atom-button", $css);
        $this->assertStringContainsString(".atom-toolbar > .atom-button-group", $css);
        $this->assertStringContainsString(".atom-toolbar > .atom-control-group", $css);
        $this->assertStringContainsString(".atom-split-view.has-side", $css);
        $this->assertStringContainsString(".atom-split-view__side", $css);
    }

    public function testComponentsTableCssDefinesActionColumnLayout(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/table.css");

        $this->assertIsString($css);
        $this->assertStringContainsString(".atom-table-stack", $css);
        $this->assertStringContainsString(".atom-table-wrap + .atom-table__footer", $css);
        $this->assertStringContainsString(".atom-table__toolbar", $css);
        $this->assertStringContainsString(".atom-table__toolbar > .atom-form", $css);
        $this->assertStringContainsString(".atom-table__toolbar .atom-toolbar", $css);
        $this->assertStringContainsString("background: transparent;", $css);
        $this->assertStringContainsString(".atom-table__footer", $css);
        $this->assertStringContainsString(".atom-table__summary", $css);
        $this->assertStringContainsString(".atom-table__sort", $css);
        $this->assertStringContainsString('.atom-table__sort-indicator[data-direction="asc"]', $css);
        $this->assertStringContainsString(".atom-table__actions", $css);
        $this->assertStringContainsString('.atom-table__cell[data-align="end"]', $css);
        $this->assertStringContainsString(".atom-table__cell--actions", $css);
    }

    public function testComponentsNavigationCssKeepsLinkTabsUnadorned(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Components/Resources/css/navigation.css");

        $this->assertIsString($css);
        $this->assertStringContainsString("a.atom-tab:hover", $css);
        $this->assertStringContainsString("text-decoration: none", $css);
        $this->assertStringContainsString("font: inherit;", $css);
        $this->assertStringContainsString("font-size: 0.92rem;", $css);
        $this->assertStringContainsString("font-weight: 500;", $css);
        $this->assertStringContainsString("appearance: none;", $css);
    }

    public function testComponentsJavascriptAllowsPageActionsInsideForms(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Client/Resources/atom.js");

        $this->assertIsString($script);
        $this->assertStringContainsString('button[type=\"submit\"], input[type=\"submit\"]', $script);
        $this->assertStringNotContainsString('element.form !== undefined && element.form !== null) {', $script);
    }

    public function testComponentsJavascriptSupportsChangeActionsWithFormContext(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Client/Resources/atom.js");

        $this->assertIsString($script);
        $this->assertStringContainsString('var changeAttribute = "atom:change";', $script);
        $this->assertStringContainsString('new FormData(form)', $script);
        $this->assertStringContainsString('"X-Atom-Event": eventName', $script);
        $this->assertStringContainsString('"X-Atom-Field": name', $script);
        $this->assertStringContainsString('meta[name="csrf-token"]', $script);
        $this->assertStringContainsString('input[name="_token"]', $script);
        $this->assertStringContainsString('headers["X-CSRF-Token"] = token;', $script);
        $this->assertStringContainsString('document.addEventListener("change"', $script);
    }

    public function testComponentsJavascriptSupportsDebouncedInputActionsWithFormContext(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2) . "/src/Modules/Client/Resources/atom.js");

        $this->assertIsString($script);
        $this->assertStringContainsString('var inputAttribute = "atom:input";', $script);
        $this->assertStringContainsString("var inputDebounceMs = 300;", $script);
        $this->assertStringContainsString("function debounceInputAction(action, field)", $script);
        $this->assertStringContainsString('document.addEventListener("input"', $script);
        $this->assertStringContainsString('invokeEventAction(action, field, "input")', $script);
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

    public string $status = "";

    public array $statuses = [];

    /** @var list<array{value: string, text: string}> */
    public array $visibilityOptions = [];

    public int $id = 0;

    public bool $published = false;

    public object $edit;
}

final class ValidationSummaryComponentPage extends Page
{
    #[Required("Title is required.")]
    public string $title = "";

    #[Required("Summary is required.")]
    public string $summary = "";
}
