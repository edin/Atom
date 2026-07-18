<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Module\ModuleContext;
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
use Atom\Modules\Components\EmptyState;
use Atom\Modules\Components\Field;
use Atom\Modules\Components\FieldError;
use Atom\Modules\Components\Form;
use Atom\Modules\Components\FormActions;
use Atom\Modules\Components\FormRow;
use Atom\Modules\Components\FormSection;
use Atom\Modules\Components\HiddenField;
use Atom\Modules\Components\Icon;
use Atom\Modules\Components\Inline;
use Atom\Modules\Components\Kbd;
use Atom\Modules\Components\ListComponent;
use Atom\Modules\Components\ListItem;
use Atom\Modules\Components\Panel;
use Atom\Modules\Components\PageHeader;
use Atom\Modules\Components\Pagination;
use Atom\Modules\Components\Progress;
use Atom\Modules\Components\RadioField;
use Atom\Modules\Components\SelectField;
use Atom\Modules\Components\Sidebar;
use Atom\Modules\Components\SidebarGroup;
use Atom\Modules\Components\SidebarItem;
use Atom\Modules\Components\SnackBar;
use Atom\Modules\Components\Skeleton;
use Atom\Modules\Components\Spinner;
use Atom\Modules\Components\SplitView;
use Atom\Modules\Components\Stack;
use Atom\Modules\Components\Stats;
use Atom\Modules\Components\StatusDot;
use Atom\Modules\Components\SwitchField;
use Atom\Modules\Components\Tab;
use Atom\Modules\Components\Tabs;
use Atom\Modules\Components\Table;
use Atom\Modules\Components\Tag;
use Atom\Modules\Components\TextArea;
use Atom\Modules\Components\TextAreaField;
use Atom\Modules\Components\TextField;
use Atom\Modules\Components\TextInput;
use Atom\Modules\Components\Toast;
use Atom\Modules\Components\Toolbar;
use Atom\Modules\Components\ValidationSummary;
use Atom\Router\RouteEntry;

final readonly class Components
{
    public const DEFAULT_RESOURCE_PATH = "/atom/components/resources";

    public static function module(string $resourcePath = self::DEFAULT_RESOURCE_PATH): ComponentsModule
    {
        return new ComponentsModule($resourcePath);
    }

    public static function components(ModuleContext $context): void
    {
        $context->component("Alert", Alert::class);
        $context->component("AppShell", AppShell::class);
        $context->component("Avatar", Avatar::class);
        $context->component("Badge", Badge::class);
        $context->component("Breadcrumb", Breadcrumb::class);
        $context->component("Breadcrumbs", Breadcrumbs::class);
        $context->component("Button", Button::class);
        $context->component("ButtonGroup", ButtonGroup::class);
        $context->component("Card", Card::class);
        $context->component("CheckField", CheckField::class);
        $context->component("Column", Column::class);
        $context->component("ControlGroup", ControlGroup::class);
        $context->component("Dialog", Dialog::class);
        $context->component("Details", Details::class);
        $context->component("Divider", Divider::class);
        $context->component("EmptyState", EmptyState::class);
        $context->component("Field", Field::class);
        $context->component("FieldError", FieldError::class);
        $context->component("Form", Form::class);
        $context->component("FormActions", FormActions::class);
        $context->component("FormRow", FormRow::class);
        $context->component("FormSection", FormSection::class);
        $context->component("HiddenField", HiddenField::class);
        $context->component("Icon", Icon::class);
        $context->component("Inline", Inline::class);
        $context->component("Kbd", Kbd::class);
        $context->component("List", ListComponent::class);
        $context->component("ListItem", ListItem::class);
        $context->component("Panel", Panel::class);
        $context->component("PageHeader", PageHeader::class);
        $context->component("Pagination", Pagination::class);
        $context->component("Progress", Progress::class);
        $context->component("RadioField", RadioField::class);
        $context->component("SelectField", SelectField::class);
        $context->component("Sidebar", Sidebar::class);
        $context->component("SidebarGroup", SidebarGroup::class);
        $context->component("SidebarItem", SidebarItem::class);
        $context->component("SnackBar", SnackBar::class);
        $context->component("Skeleton", Skeleton::class);
        $context->component("Spinner", Spinner::class);
        $context->component("SplitView", SplitView::class);
        $context->component("Stack", Stack::class);
        $context->component("Stats", Stats::class);
        $context->component("StatusDot", StatusDot::class);
        $context->component("SwitchField", SwitchField::class);
        $context->component("Tab", Tab::class);
        $context->component("Tabs", Tabs::class);
        $context->component("Table", Table::class);
        $context->component("Tag", Tag::class);
        $context->component("TextArea", TextArea::class);
        $context->component("TextAreaField", TextAreaField::class);
        $context->component("TextField", TextField::class);
        $context->component("TextInput", TextInput::class);
        $context->component("Toast", Toast::class);
        $context->component("Toolbar", Toolbar::class);
        $context->component("ValidationSummary", ValidationSummary::class);
    }

    /**
     * @return RouteEntry[]
     */
    public static function resources(ModuleContext $context, string $path = self::DEFAULT_RESOURCE_PATH): array
    {
        return $context->resources($path, __DIR__ . "/Resources");
    }
}
