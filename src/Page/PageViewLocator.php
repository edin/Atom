<?php

declare(strict_types=1);

namespace Atom\Page;

use ReflectionClass;

final readonly class PageViewLocator
{
    /**
     * @param class-string<Page> $pageClass
     */
    public function locate(string $pageClass): string
    {
        $reflection = new ReflectionClass($pageClass);
        $template = $this->customTemplate($reflection);

        if ($template !== null) {
            return $this->resolveCustomTemplate($reflection, $template);
        }

        $fileName = $reflection->getFileName();
        if ($fileName === false) {
            throw new PageViewLocatorException("Cannot locate view for page '{$pageClass}'.");
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
