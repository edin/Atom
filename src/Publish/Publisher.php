<?php

declare(strict_types=1);

namespace Atom\Publish;

use Atom\Support\Paths;
use InvalidArgumentException;

final readonly class Publisher
{
    public function __construct(private Paths $paths)
    {
    }

    public function publish(PublishBundle $bundle, bool $force = false): PublishResult
    {
        $files = $this->resolve($bundle);
        $published = [];
        $skipped = [];
        $overwritten = [];

        foreach ($files as [$source, $destination]) {
            $exists = file_exists($destination);
            if ($exists && !$force) {
                $skipped[] = $destination;
                continue;
            }

            $this->createDirectory(dirname($destination));
            $contents = file_get_contents($source);
            if ($contents === false) {
                throw new PublishException("Cannot read publish source '{$source}'.");
            }

            $written = file_put_contents($destination, $contents, LOCK_EX);
            if ($written === false || $written !== strlen($contents)) {
                throw new PublishException("Cannot publish file to '{$destination}'.");
            }

            if ($exists) {
                $overwritten[] = $destination;
            } else {
                $published[] = $destination;
            }
        }

        return new PublishResult($bundle->name, $published, $skipped, $overwritten);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function resolve(PublishBundle $bundle): array
    {
        $files = [];
        $destinations = [];

        foreach ($bundle->files() as $file) {
            $source = $this->resolvePath($file->source);
            $destination = $this->resolvePath($file->destination);

            if (!is_file($source) || !is_readable($source)) {
                throw new PublishException("Publish source '{$source}' does not exist or is not a readable file.");
            }

            if (file_exists($destination) && !is_file($destination)) {
                throw new PublishException("Publish destination '{$destination}' exists and is not a file.");
            }

            $destinationKey = PHP_OS_FAMILY === "Windows" ? strtolower($destination) : $destination;
            if (isset($destinations[$destinationKey])) {
                throw new PublishException(
                    "Publish bundle '{$bundle->name}' defines destination '{$destination}' more than once."
                );
            }

            $destinations[$destinationKey] = true;
            $files[] = [$source, $destination];
        }

        return $files;
    }

    private function resolvePath(string $path): string
    {
        try {
            $resolved = $this->paths->resolveFrom("@root", $path);
        } catch (InvalidArgumentException $exception) {
            throw new PublishException("Cannot resolve publish path '{$path}'.", previous: $exception);
        }

        return str_replace("/", DIRECTORY_SEPARATOR, $resolved);
    }

    private function createDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new PublishException("Cannot create publish directory '{$directory}'.");
        }
    }
}
