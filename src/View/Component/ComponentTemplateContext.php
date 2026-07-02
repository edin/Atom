<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Atom\View\Html;
use Atom\View\Render\HtmlString;

final readonly class ComponentTemplateContext
{
    public function encode(mixed $value): string
    {
        return Html::escape($value);
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function fragment(Fragment|TemplateFragment|null $fragment, array $variables = []): string
    {
        return $fragment?->render($variables) ?? "";
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function fragmentHtml(Fragment|TemplateFragment|null $fragment, array $variables = []): HtmlString
    {
        return new HtmlString($fragment?->render($variables) ?? "");
    }

    public function raw(mixed $value): HtmlString
    {
        return new HtmlString((string) $value);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function attributes(array $attributes): string
    {
        return (new AttributeBag($attributes))->render();
    }

    /**
     * @param array<int|string, mixed> $classes
     */
    public function classes(array $classes): string
    {
        $result = [];

        foreach ($classes as $class => $enabled) {
            if (is_int($class)) {
                if (is_string($enabled) && $enabled !== "") {
                    $result[] = $enabled;
                }

                continue;
            }

            if ($enabled) {
                $result[] = $class;
            }
        }

        return implode(" ", $result);
    }
}
