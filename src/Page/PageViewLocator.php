<?php

declare(strict_types=1);

namespace Atom\Page;

use ReflectionClass;
use ReflectionObject;

final readonly class PageViewLocator
{
    /**
     * @param Page|class-string<Page> $page
     */
    public function locate(Page|string $page): string
    {
        $reflection = is_object($page) ? new ReflectionObject($page) : new ReflectionClass($page);
        $template = is_object($page) ? $page->template() : $this->customTemplate($reflection);

        if ($template !== null) {
            return $this->resolveCustomTemplate($reflection, $template);
        }

        $fileName = $reflection->getFileName();
        if ($fileName === false) {
            throw new PageViewLocatorException("Cannot locate view for page '{$reflection->getName()}'.");
        }

        $path = dirname($fileName) . DIRECTORY_SEPARATOR . pathinfo($fileName, PATHINFO_FILENAME) . ".atom.html";

        if (!is_file($path)) {
            throw new PageViewLocatorException("Cannot find page view '{$path}'.");
        }

        return $path;
    }

    /**
     * @param ReflectionClass<Page> $reflection
     */
    private function customTemplate(ReflectionClass $reflection): ?string
    {
        $page = $reflection->newInstanceWithoutConstructor();

        return $page->template();
    }

    /**
     * @param ReflectionClass<Page> $reflection
     */
    private function resolveCustomTemplate(ReflectionClass $reflection, string $template): string
    {
        if ($this->isAbsolutePath($template)) {
            return $this->existingPath($template);
        }

        $fileName = $reflection->getFileName();
        if ($fileName === false) {
            throw new PageViewLocatorException("Cannot locate view for page '{$reflection->getName()}'.");
        }

        return $this->existingPath(dirname($fileName) . DIRECTORY_SEPARATOR . $template);
    }

    private function existingPath(string $path): string
    {
        if (!is_file($path)) {
            throw new PageViewLocatorException("Cannot find page view '{$path}'.");
        }

        return $path;
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1
            || str_starts_with($path, DIRECTORY_SEPARATOR);
    }
}
