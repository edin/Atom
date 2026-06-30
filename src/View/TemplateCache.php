<?php

declare(strict_types=1);

namespace Atom\View;

use Atom\View\Ast\TemplateNode;
use RuntimeException;

final class TemplateCache
{
    /** @var array<string, TemplateNode> */
    private array $items = [];

    /**
     * @param callable(): TemplateNode $factory
     */
    public function remember(string $path, callable $factory): TemplateNode
    {
        $key = $this->key($path);
        if (!isset($this->items[$key])) {
            $this->items[$key] = $factory();
        }

        return $this->items[$key];
    }

    public function clear(): void
    {
        $this->items = [];
    }

    private function key(string $path): string
    {
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            throw new RuntimeException("Template file '{$path}' was not found.");
        }

        $modifiedAt = filemtime($realPath);
        if ($modifiedAt === false) {
            throw new RuntimeException("Template file '{$path}' modified time could not be read.");
        }

        return str_replace("\\", "/", $realPath) . ":" . $modifiedAt;
    }
}
