<?php

declare(strict_types=1);

namespace Atom\Console;

use RuntimeException;

final readonly class FileTemplateRenderer
{
    /**
     * @var list<string>
     */
    private array $roots;

    /**
     * @param string|list<string> $roots
     */
    public function __construct(string|array $roots = __DIR__ . "/../Templates")
    {
        $this->roots = array_values(is_array($roots) ? $roots : [$roots]);
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    public function render(string $template, array $variables = []): string
    {
        $path = $this->path($template);
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Template '{$template}' could not be read.");
        }

        foreach ($variables as $name => $value) {
            $contents = str_replace("{{ {$name} }}", (string) $value, $contents);
        }

        return $contents;
    }

    private function path(string $template): string
    {
        foreach ($this->roots as $root) {
            $path = rtrim($root, "/\\") . DIRECTORY_SEPARATOR . ltrim($template, "/\\");
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException("Template '{$template}' does not exist.");
    }
}
