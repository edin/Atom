<?php

declare(strict_types=1);

namespace Showcase\Components;

use Atom\View\Component\ComponentInterface;
use Atom\View\Component\TemplateFragment;
use Atom\View\Html;

final class ComponentExample implements ComponentInterface
{
    public string $title = "";

    public string $description = "";

    public ?TemplateFragment $content = null;

    public function render(): string
    {
        return Html::tag("section", ["class" => "component-example"],
            $this->header() .
            Html::tag("div", ["class" => "component-example-preview"], $this->content?->render() ?? "") .
            Html::tag("pre", ["class" => "component-example-code"], Html::tag(
                "code",
                [],
                $this->highlight($this->normalizeSource($this->content?->source() ?? ""))
            ))
        );
    }

    private function header(): string
    {
        $content = Html::tag("h2", ["class" => "component-example-title"], Html::escape($this->title));

        if ($this->description !== "") {
            $content .= Html::tag("p", ["class" => "component-example-description"], Html::escape($this->description));
        }

        return Html::tag("header", ["class" => "component-example-header"], $content);
    }

    private function normalizeSource(string $source): string
    {
        $source = trim($source, "\r\n");
        $lines = preg_split('/\R/', $source) ?: [];
        $indents = [];

        foreach ($lines as $line) {
            if (trim($line) === "") {
                continue;
            }

            preg_match('/^[ \t]*/', $line, $match);
            $indents[] = strlen(str_replace("\t", "    ", $match[0] ?? ""));
        }

        $indent = $indents === [] ? 0 : min($indents);
        if ($indent === 0) {
            return $source;
        }

        return implode("\n", array_map(
            static fn(string $line): string => preg_replace('/^[ \t]{0,' . $indent . '}/', "", $line, 1) ?? $line,
            $lines
        ));
    }

    private function highlight(string $source): string
    {
        $parts = preg_split('/(<(?:[^>"\']+|"[^"]*"|\'[^\']*\')+>|\{\{.*?\}\}|@\w+(?:\([^)]*\))?)/s', $source, flags: PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return Html::escape($source);
        }

        $html = "";
        foreach ($parts as $part) {
            if ($part === "") {
                continue;
            }

            if (str_starts_with($part, "<")) {
                $html .= $this->highlightTag($part);
                continue;
            }

            if (str_starts_with($part, "{{")) {
                $html .= Html::tag("span", ["class" => "code-expression"], Html::escape($part));
                continue;
            }

            if (str_starts_with($part, "@")) {
                $html .= Html::tag("span", ["class" => "code-directive"], Html::escape($part));
                continue;
            }

            $html .= Html::escape($part);
        }

        return $html;
    }

    private function highlightTag(string $source): string
    {
        return preg_replace_callback('/("[^"]*"|\'[^\']*\'|<\/?|\/?>|=|[A-Za-z_:][\w:.-]*)/', function (array $match): string {
            $token = $match[0];

            if ($token === "<" || $token === "</" || $token === ">" || $token === "/>" || $token === "=") {
                return Html::tag("span", ["class" => "code-punctuation"], Html::escape($token));
            }

            if (str_starts_with($token, '"') || str_starts_with($token, "'")) {
                return Html::tag("span", ["class" => "code-string"], Html::escape($token));
            }

            return Html::tag("span", ["class" => "code-name"], Html::escape($token));
        }, $source) ?? Html::escape($source);
    }
}
