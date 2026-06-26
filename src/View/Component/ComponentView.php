<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Atom\View\Render\ViewRenderException;
use ReflectionObject;
use ReflectionProperty;
use Throwable;

final readonly class ComponentView
{
    /**
     * @param array<string, mixed> $variables
     */
    public static function render(ComponentInterface $component, ?string $template = null, array $variables = []): string
    {
        $path = self::templatePath($component, $template);
        $variables = [
            "component" => $component,
            "context" => new ComponentTemplateContext(),
            ...self::publicProperties($component),
            ...$variables,
        ];

        try {
            ob_start();

            $include = function (string $__path, array $__variables): void {
                extract($__variables, EXTR_SKIP);
                include $__path;
            };

            $include->bindTo($component, $component::class)?->__invoke($path, $variables);

            return ob_get_clean() ?: "";
        } catch (Throwable $exception) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw new ViewRenderException("Failed to render component view '{$path}'.", previous: $exception);
        }
    }

    private static function templatePath(ComponentInterface $component, ?string $template): string
    {
        $reflection = new ReflectionObject($component);
        $fileName = $reflection->getFileName();

        if ($fileName === false) {
            throw new ViewRenderException("Cannot locate component view for '{$reflection->getName()}'.");
        }

        $path = $template ?? dirname($fileName) . DIRECTORY_SEPARATOR . $reflection->getShortName() . ".atom.php";

        if (!self::isAbsolutePath($path)) {
            $path = dirname($fileName) . DIRECTORY_SEPARATOR . $path;
        }

        if (!is_file($path)) {
            throw new ViewRenderException("Cannot find component view '{$path}'.");
        }

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private static function publicProperties(ComponentInterface $component): array
    {
        $reflection = new ReflectionObject($component);
        $variables = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || !$property->isInitialized($component)) {
                continue;
            }

            $variables[$property->getName()] = $property->getValue($component);
        }

        return $variables;
    }

    private static function isAbsolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1
            || str_starts_with($path, DIRECTORY_SEPARATOR);
    }
}
