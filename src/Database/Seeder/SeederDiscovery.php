<?php

declare(strict_types=1);

namespace Atom\Database\Seeder;

use RuntimeException;

final class SeederDiscovery
{
    /**
     * @return SeederDefinition[]
     */
    public function discover(SeederOptions $options): array
    {
        if (!$options->hasSource() || !is_dir($options->directory)) {
            return [];
        }

        $directory = rtrim(str_replace("\\", "/", realpath($options->directory) ?: $options->directory), "/");
        $namespace = trim($options->namespace, "\\");
        $definitions = [];

        foreach ($this->files($directory) as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $seeder = require $file;

            if ($seeder instanceof Seeder) {
                $definitions[] = new SeederDefinition($name, static function () use ($file, $name): Seeder {
                    $seeder = require $file;

                    if (!$seeder instanceof Seeder) {
                        throw new RuntimeException("Seeder '{$name}' must return a Seeder instance.");
                    }

                    return $seeder;
                });
                continue;
            }

            $className = $this->className($directory, $namespace, $file);
            if ($className !== null && class_exists($className) && is_subclass_of($className, Seeder::class)) {
                $definitions[] = new SeederDefinition($name, static fn(): Seeder => new $className());
            }
        }

        usort($definitions, static fn(SeederDefinition $a, SeederDefinition $b): int => $a->name <=> $b->name);

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
