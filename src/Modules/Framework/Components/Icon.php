<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;
use Atom\Support\Paths;
use RuntimeException;

final class Icon implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $text = "";
    public string $src = "";
    public string $icon = "";
    public string $variant = "";
    public string $size = "";
    public string $class = "";

    public function __construct(private ?Paths $paths = null)
    {
    }

    public static function from(string $value, ?Paths $paths = null): self
    {
        $component = new self($paths);
        $component->attributes = new AttributeBag();

        if (self::looksLikeSource($value)) {
            $component->src = $value;
        } else {
            $component->icon = $value;
        }

        return $component;
    }

    public function render(): string
    {
        return Html::tag("span", Html::mergeAttributes([
            "class" => Html::classes("atom-icon", $this->class),
            "data-variant" => $this->variant,
            "data-size" => $this->size,
        ], $this->attributes->all()), $this->content());
    }

    private function content(): string
    {
        if ($this->content !== null) {
            return $this->content->renderOr(Html::escape($this->text));
        }

        if ($this->src !== "") {
            return $this->sourceContent();
        }

        if ($this->icon !== "") {
            return Html::tag("i", ["class" => $this->icon, "aria-hidden" => "true"]);
        }

        return Html::escape($this->text);
    }

    private function sourceContent(): string
    {
        if ($this->isPublicSource($this->src)) {
            return '<img src="' . Html::escape($this->src) . '" alt="">';
        }

        $path = $this->paths?->resolve($this->src) ?? $this->src;
        if (!is_file($path)) {
            throw new RuntimeException("Icon source '{$this->src}' was not found.");
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== "svg") {
            throw new RuntimeException("Icon source '{$this->src}' must be an SVG file.");
        }

        $svg = file_get_contents($path);
        if ($svg === false) {
            throw new RuntimeException("Icon source '{$this->src}' could not be read.");
        }

        $svg = trim(preg_replace('/^\s*<\?xml[^>]*>\s*/i', "", $svg) ?? $svg);
        if (!str_starts_with(strtolower($svg), "<svg")) {
            throw new RuntimeException("Icon source '{$this->src}' does not contain SVG markup.");
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
