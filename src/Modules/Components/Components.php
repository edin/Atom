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
use Atom\View\Component\ComponentSet;

final readonly class Components
{
    public const DEFAULT_RESOURCE_PATH = "/atom/components/resources";
    public const STYLES_VERSION = "1";

    public static function module(string $resourcePath = self::DEFAULT_RESOURCE_PATH): ComponentsModule
    {
        return new ComponentsModule($resourcePath);
    }

    public static function definitions(): ComponentSet
    {
        return ComponentSet::from([
            "Alert" => Alert::class,
            "AppShell" => AppShell::class,
            "Avatar" => Avatar::class,
            "Badge" => Badge::class,
            "Breadcrumb" => Breadcrumb::class,
            "Breadcrumbs" => Breadcrumbs::class,
            "Button" => Button::class,
            "ButtonGroup" => ButtonGroup::class,
            "Card" => Card::class,
            "CheckField" => CheckField::class,
            "Column" => Column::class,
            "ComponentsStyles" => ComponentsStyles::class,
            "ControlGroup" => ControlGroup::class,
            "Dialog" => Dialog::class,
            "Details" => Details::class,
            "Divider" => Divider::class,
            "EmptyState" => EmptyState::class,
            "Field" => Field::class,
            "FieldError" => FieldError::class,
            "Form" => Form::class,
            "FormActions" => FormActions::class,
            "FormRow" => FormRow::class,
            "FormSection" => FormSection::class,
            "HiddenField" => HiddenField::class,
            "Icon" => Icon::class,
            "Inline" => Inline::class,
            "Kbd" => Kbd::class,
            "List" => ListComponent::class,
            "ListItem" => ListItem::class,
            "Panel" => Panel::class,
            "PageHeader" => PageHeader::class,
            "Pagination" => Pagination::class,
            "Progress" => Progress::class,
            "RadioField" => RadioField::class,
            "SelectField" => SelectField::class,
            "Sidebar" => Sidebar::class,
            "SidebarGroup" => SidebarGroup::class,
            "SidebarItem" => SidebarItem::class,
            "SnackBar" => SnackBar::class,
            "Skeleton" => Skeleton::class,
            "Spinner" => Spinner::class,
            "SplitView" => SplitView::class,
            "Stack" => Stack::class,
            "Stats" => Stats::class,
            "StatusDot" => StatusDot::class,
            "SwitchField" => SwitchField::class,
            "Tab" => Tab::class,
            "Tabs" => Tabs::class,
            "Table" => Table::class,
            "Tag" => Tag::class,
            "TextArea" => TextArea::class,
            "TextAreaField" => TextAreaField::class,
            "TextField" => TextField::class,
            "TextInput" => TextInput::class,
            "Toast" => Toast::class,
            "Toolbar" => Toolbar::class,
            "ValidationSummary" => ValidationSummary::class,
        ]);
    }

    /**
     * @return RouteEntry[]
     */
    public static function resources(ModuleContext $context, string $path = self::DEFAULT_RESOURCE_PATH): array
    {
        return $context->resources($path, __DIR__ . "/Resources");
    }
}
