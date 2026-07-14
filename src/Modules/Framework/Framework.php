<?php

declare(strict_types=1);

namespace Atom\Modules\Framework;

use Atom\Module\ModuleContext;
use Atom\Modules\Framework\Components\Alert;
use Atom\Modules\Framework\Components\AppShell;
use Atom\Modules\Framework\Components\Badge;
use Atom\Modules\Framework\Components\Breadcrumb;
use Atom\Modules\Framework\Components\Breadcrumbs;
use Atom\Modules\Framework\Components\Button;
use Atom\Modules\Framework\Components\ButtonGroup;
use Atom\Modules\Framework\Components\Card;
use Atom\Modules\Framework\Components\CheckField;
use Atom\Modules\Framework\Components\Column;
use Atom\Modules\Framework\Components\ControlGroup;
use Atom\Modules\Framework\Components\Dialog;
use Atom\Modules\Framework\Components\EmptyState;
use Atom\Modules\Framework\Components\Field;
use Atom\Modules\Framework\Components\FieldError;
use Atom\Modules\Framework\Components\Form;
use Atom\Modules\Framework\Components\FormActions;
use Atom\Modules\Framework\Components\FormRow;
use Atom\Modules\Framework\Components\FormSection;
use Atom\Modules\Framework\Components\HiddenField;
use Atom\Modules\Framework\Components\Icon;
use Atom\Modules\Framework\Components\Inline;
use Atom\Modules\Framework\Components\ListComponent;
use Atom\Modules\Framework\Components\ListItem;
use Atom\Modules\Framework\Components\Panel;
use Atom\Modules\Framework\Components\PageHeader;
use Atom\Modules\Framework\Components\Pagination;
use Atom\Modules\Framework\Components\SelectField;
use Atom\Modules\Framework\Components\Sidebar;
use Atom\Modules\Framework\Components\SidebarGroup;
use Atom\Modules\Framework\Components\SidebarItem;
use Atom\Modules\Framework\Components\SnackBar;
use Atom\Modules\Framework\Components\SplitView;
use Atom\Modules\Framework\Components\Stack;
use Atom\Modules\Framework\Components\Stats;
use Atom\Modules\Framework\Components\Tab;
use Atom\Modules\Framework\Components\Tabs;
use Atom\Modules\Framework\Components\Table;
use Atom\Modules\Framework\Components\TextArea;
use Atom\Modules\Framework\Components\TextAreaField;
use Atom\Modules\Framework\Components\TextField;
use Atom\Modules\Framework\Components\TextInput;
use Atom\Modules\Framework\Components\Toast;
use Atom\Modules\Framework\Components\Toolbar;
use Atom\Modules\Framework\Components\ValidationSummary;
use Atom\Router\RouteEntry;

final readonly class Framework
{
    public const DEFAULT_RESOURCE_PATH = "/atom/framework/resources";

    public static function module(string $resourcePath = self::DEFAULT_RESOURCE_PATH): FrameworkModule
    {
        return new FrameworkModule($resourcePath);
    }

    public static function components(ModuleContext $context): void
    {
        $context->component("Alert", Alert::class);
        $context->component("AppShell", AppShell::class);
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
        $context->component("List", ListComponent::class);
        $context->component("ListItem", ListItem::class);
        $context->component("Panel", Panel::class);
        $context->component("PageHeader", PageHeader::class);
        $context->component("Pagination", Pagination::class);
        $context->component("SelectField", SelectField::class);
        $context->component("Sidebar", Sidebar::class);
        $context->component("SidebarGroup", SidebarGroup::class);
        $context->component("SidebarItem", SidebarItem::class);
        $context->component("SnackBar", SnackBar::class);
        $context->component("SplitView", SplitView::class);
        $context->component("Stack", Stack::class);
        $context->component("Stats", Stats::class);
        $context->component("Tab", Tab::class);
        $context->component("Tabs", Tabs::class);
        $context->component("Table", Table::class);
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
