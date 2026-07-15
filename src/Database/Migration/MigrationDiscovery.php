<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

use RuntimeException;

final class MigrationDiscovery
{
    /**
     * @return MigrationDefinition[]
     */
    public function discover(MigrationOptions $options): array
    {
        if (!$options->hasSource() || !is_dir($options->directory)) {
            return [];
        }

        $directory = rtrim(str_replace("\\", "/", realpath($options->directory) ?: $options->directory), "/");
        $namespace = trim($options->namespace, "\\");
        $definitions = [];

        foreach ($this->files($directory) as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $migration = require $file;

            if ($migration instanceof Migration) {
                $definitions[] = new MigrationDefinition($name, static function () use ($file, $name): Migration {
                    $migration = require $file;

                    if (!$migration instanceof Migration) {
                        throw new RuntimeException("Migration '{$name}' must return a Migration instance.");
                    }

                    return $migration;
                });
                continue;
            }

            $className = $this->className($directory, $namespace, $file);
            if ($className !== null && class_exists($className) && is_subclass_of($className, Migration::class)) {
                $definitions[] = new MigrationDefinition($name, static fn(): Migration => new $className());
            }
        }

        usort($definitions, static fn(MigrationDefinition $a, MigrationDefinition $b): int => $a->name <=> $b->name);

        return $definitions;
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
        if ($namespace === "") {
            return null;
        }

        $relative = substr($file, strlen($directory) + 1);

        if (!str_ends_with($relative, ".php")) {
            return null;
        }

        $relative = substr($relative, 0, -4);
        $class = str_replace("/", "\\", $relative);

        return $namespace . "\\" . $class;
    }
}
