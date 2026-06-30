<?php

declare(strict_types=1);

namespace Atom\View;

final readonly class Html
{
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function attributes(array $attributes, ?callable $escape = null): string
    {
        $html = "";

        foreach ($attributes as $name => $value) {
            $html .= self::attribute($name, $value, $escape);
        }

        return $html;
    }

    public static function attribute(string $name, mixed $value, ?callable $escape = null): string
    {
        if ($value === false || $value === null || $value === "") {
            return "";
        }

        if ($value === true) {
            return " " . $name;
        }

        $escape ??= self::escape(...);

        return " " . $name . '="' . $escape($value) . '"';
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function tag(string $name, array $attributes = [], string $content = ""): string
    {
        return "<{$name}" . self::attributes($attributes) . ">{$content}</{$name}>";
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function voidTag(string $name, array $attributes = []): string
    {
        return "<{$name}" . self::attributes($attributes) . ">";
    }

    /**
     * @param mixed ...$values string values, arrays of class names, or arrays of class => condition
     */
    public static function classes(mixed ...$values): string
    {
        $classes = [];

        foreach ($values as $value) {
            foreach (self::classTokens($value) as $class) {
                $classes[$class] = $class;
            }
        }

        return implode(" ", $classes);
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public static function mergeAttributes(array $defaults, array $attributes): array
    {
        if (isset($defaults["class"]) || isset($attributes["class"])) {
            $attributes["class"] = self::classes($defaults["class"] ?? "", $attributes["class"] ?? "");
        }

        return [...$defaults, ...$attributes];
    }

    /**
     * @return string[]
     */
    private static function classTokens(mixed $value): array
    {
        if ($value === null || $value === false || $value === "") {
            return [];
        }

        if (is_string($value) || is_numeric($value)) {
            return preg_split('/\s+/', trim((string) $value), flags: PREG_SPLIT_NO_EMPTY) ?: [];
        }

        if (!is_iterable($value)) {
            return [];
        }

        $classes = [];
        foreach ($value as $class => $condition) {
            if (is_int($class)) {
                $classes = [...$classes, ...self::classTokens($condition)];
                continue;
            }

            if ($condition) {
                $classes = [...$classes, ...self::classTokens($class)];
            }
        }

        return $classes;
    }
}
