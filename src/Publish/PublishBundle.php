<?php

declare(strict_types=1);

namespace Atom\Publish;

use InvalidArgumentException;

final class PublishBundle
{
    /** @var PublishFile[] */
    private array $files = [];

    public function __construct(
        public readonly string $name,
        public readonly string $sourceDirectory = ""
    ) {
        if (trim($this->name) === "") {
            throw new InvalidArgumentException("Publish bundle name cannot be empty.");
        }
    }

    public function file(string $source, string $destination): self
    {
        $this->files[] = new PublishFile($this->sourcePath($source), $destination);

        return $this;
    }

    /**
     * @return PublishFile[]
     */
    public function files(): array
    {
        return $this->files;
    }

    private function sourcePath(string $source): string
    {
        if (
            $this->sourceDirectory === ""
            || str_starts_with($source, "@")
            || $this->isAbsolute($source)
        ) {
            return $source;
        }

        return rtrim($this->sourceDirectory, "/\\") . DIRECTORY_SEPARATOR . ltrim($source, "/\\");
    }

    private function isAbsolute(string $path): bool
    {
        $path = str_replace("\\", "/", $path);

        return preg_match('/^(?:[A-Za-z]:\/|\/)/', $path) === 1;
    }
}
