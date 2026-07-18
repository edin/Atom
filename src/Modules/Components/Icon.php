<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;
use Atom\Support\Paths;
use RuntimeException;

final class Icon implements ComponentInterface
{
    private const LUCIDE_PREFIX = "lucide:";

    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $text = "";
    public string $icon = "";
    public string $variant = "";
    public string $appearance = "";
    public string $size = "";
    public string $class = "";

    public function __construct(private ?Paths $paths = null)
    {
    }

    public static function from(string $value, ?Paths $paths = null): self
    {
        $component = new self($paths);
        $component->attributes = new AttributeBag();
        $component->icon = $value;

        return $component;
    }

    public function render(): string
    {
        return Html::tag("span", Html::mergeAttributes([
            "class" => Html::classes("atom-icon", $this->class),
            "data-variant" => $this->variant,
            "data-appearance" => $this->appearance,
            "data-size" => $this->size,
        ], $this->attributes->all()), $this->content());
    }

    private function content(): string
    {
        if ($this->content !== null) {
            return $this->content->renderOr(Html::escape($this->text));
        }

        if ($this->icon !== "") {
            if (str_starts_with($this->icon, self::LUCIDE_PREFIX)) {
                return $this->lucideContent(substr($this->icon, strlen(self::LUCIDE_PREFIX)));
            }

            if (self::looksLikeSource($this->icon)) {
                return $this->sourceContent($this->icon);
            }

            return Html::tag("i", ["class" => $this->icon, "aria-hidden" => "true"]);
        }

        return Html::escape($this->text);
    }

    private function sourceContent(string $source): string
    {
        if ($this->isPublicSource($source)) {
            return '<img src="' . Html::escape($source) . '" alt="">';
        }

        $path = $this->paths?->resolve($source) ?? $source;
        if (!is_file($path)) {
            throw new RuntimeException("Icon '{$source}' was not found.");
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== "svg") {
            throw new RuntimeException("Icon '{$source}' must be an SVG file.");
        }

        return $this->readSvg($path, "Icon '{$source}'");
    }

    private function lucideContent(string $name): string
    {
        if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $name)) {
            throw new RuntimeException("Lucide icon name '{$name}' is invalid.");
        }

        $path = __DIR__ . "/Resources/icons/lucide/{$name}.svg";
        if (!is_file($path)) {
            throw new RuntimeException("Lucide icon '{$name}' was not found.");
        }

        return $this->readSvg($path, "Lucide icon '{$name}'");
    }

    private function readSvg(string $path, string $label): string
    {
        $svg = file_get_contents($path);
        if ($svg === false) {
            throw new RuntimeException("{$label} could not be read.");
        }

        $svg = trim(preg_replace('/^\s*<\?xml[^>]*>\s*/i', "", $svg) ?? $svg);
        $svg = trim(preg_replace('/^\s*<!--.*?-->\s*/s', "", $svg) ?? $svg);
        if (!str_starts_with(strtolower($svg), "<svg")) {
            throw new RuntimeException("{$label} does not contain SVG markup.");
        }

        return $svg;
    }

    private function isPublicSource(string $source): bool
    {
        return str_starts_with($source, "/")
            || preg_match('/^https?:\/\//i', $source) === 1
            || str_starts_with($source, "data:");
    }

    private static function looksLikeSource(string $value): bool
    {
        return str_ends_with(strtolower(parse_url($value, PHP_URL_PATH) ?? $value), ".svg")
            || str_starts_with($value, "/")
            || str_starts_with($value, "@")
            || preg_match('/^https?:\/\//i', $value) === 1
            || str_starts_with($value, "data:");
    }
}
