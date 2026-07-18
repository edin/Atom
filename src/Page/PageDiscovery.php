<?php

declare(strict_types=1);

namespace Atom\Page;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final readonly class PageDiscovery
{
    /**
     * @return PageDescriptor[]
     */
    public function discover(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new PageDiscoveryException("Page directory '{$directory}' does not exist.");
        }

        $directory = realpath($directory) ?: $directory;
        $descriptors = [];
        $classes = [];

        foreach ($this->phpFiles($directory) as $file) {
            foreach ($this->loadClasses($file) as $className) {
                $classes[$className] = true;
            }
        }

        foreach (get_declared_classes() as $className) {
            if ($this->classLivesInDirectory($className, $directory)) {
                $classes[$className] = true;
            }
        }

        foreach (array_keys($classes) as $className) {
            $descriptors = [...$descriptors, ...$this->descriptorsFor($className)];
        }

        return $descriptors;
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFiles(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== "php") {
                continue;
            }

            yield $file;
        }
    }

    /**
     * @return class-string[]
     */
    private function loadClasses(SplFileInfo $file): array
    {
        $before = get_declared_classes();
        require_once $file->getPathname();
        $after = get_declared_classes();

        return array_values(array_diff($after, $before));
    }

    /**
     * @param class-string $className
     */
    private function classLivesInDirectory(string $className, string $directory): bool
    {
        $reflection = new ReflectionClass($className);
        $fileName = $reflection->getFileName();

        if ($fileName === false) {
            return false;
        }

        $fileName = realpath($fileName) ?: $fileName;

        return str_starts_with($fileName, rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    }

    /**
     * @param class-string $className
     * @return PageDescriptor[]
     */
    private function descriptorsFor(string $className): array
    {
        if (!is_subclass_of($className, Page::class)) {
            return [];
        }

        $reflection = new ReflectionClass($className);
        $routes = $reflection->getAttributes(PageRoute::class);
        $descriptors = [];

        foreach ($routes as $routeAttribute) {
            /** @var PageRoute $route */
            $route = $routeAttribute->newInstance();
            $descriptors[] = new PageDescriptor(
                $route->path,
                $className,
                $route->name,
                $route->middlewares,
                $route->title,
                $route->description
            );
        }

        return $descriptors;
    }
}
