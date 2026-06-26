<?php

declare(strict_types=1);

namespace Atom\Console;

use Atom\Console\Attributes\ConsoleCommand;
use ReflectionClass;
use ReflectionMethod;

final class CommandDiscovery
{
    /**
     * @return class-string[]
     */
    public function discover(string $directory, string $namespace): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $directory = rtrim(str_replace("\\", "/", realpath($directory) ?: $directory), "/");
        $namespace = trim($namespace, "\\");
        $classes = [];

        foreach ($this->files($directory) as $file) {
            $className = $this->className($directory, $namespace, $file);

            if ($className !== null && class_exists($className)) {
                $classes[] = $className;
            }
        }

        sort($classes);

        return $classes;
    }

    public function isDiscoverableCommand(string $className): bool
    {
        if (is_subclass_of($className, Command::class)) {
            return true;
        }

        $reflection = new ReflectionClass($className);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getAttributes(ConsoleCommand::class) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function files(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === "php") {
                $files[] = str_replace("\\", "/", $file->getPathname());
            }
        }

        sort($files);

        return $files;
    }

    private function className(string $directory, string $namespace, string $file): ?string
    {
        $relative = substr($file, strlen($directory) + 1);

        if ($relative === false || !str_ends_with($relative, ".php")) {
            return null;
        }

        $relative = substr($relative, 0, -4);
        $class = str_replace("/", "\\", $relative);

        return $namespace . "\\" . $class;
    }
}

