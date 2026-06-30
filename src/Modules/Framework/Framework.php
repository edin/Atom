<?php

declare(strict_types=1);

namespace Atom\Modules\Framework;

use Atom\Module\ModuleContext;
use Atom\Modules\Framework\Components\Alert;
use Atom\Modules\Framework\Components\Badge;
use Atom\Modules\Framework\Components\Button;
use Atom\Modules\Framework\Components\Field;
use Atom\Modules\Framework\Components\FieldError;
use Atom\Modules\Framework\Components\Form;
use Atom\Modules\Framework\Components\FormActions;
use Atom\Modules\Framework\Components\Inline;
use Atom\Modules\Framework\Components\Panel;
use Atom\Modules\Framework\Components\SelectField;
use Atom\Modules\Framework\Components\Stack;
use Atom\Modules\Framework\Components\TextArea;
use Atom\Modules\Framework\Components\TextAreaField;
use Atom\Modules\Framework\Components\TextField;
use Atom\Modules\Framework\Components\TextInput;
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
        $context->component("Badge", Badge::class);
        $context->component("Button", Button::class);
        $context->component("Field", Field::class);
        $context->component("FieldError", FieldError::class);
        $context->component("Form", Form::class);
        $context->component("FormActions", FormActions::class);
        $context->component("Inline", Inline::class);
        $context->component("Panel", Panel::class);
        $context->component("SelectField", SelectField::class);
        $context->component("Stack", Stack::class);
        $context->component("TextArea", TextArea::class);
        $context->component("TextAreaField", TextAreaField::class);
        $context->component("TextField", TextField::class);
        $context->component("TextInput", TextInput::class);
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
