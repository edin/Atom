<?php

declare(strict_types=1);

namespace Atom\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements IteratorAggregate<string, UploadedFile|array<int, UploadedFile>>
 */
final class FileCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<string, UploadedFile|array<int, UploadedFile>> */
    private array $files = [];

    /**
     * @param array<string, mixed> $files
     */
    public function __construct(private array $rawFiles = [])
    {
        foreach ($rawFiles as $name => $file) {
            if (!is_array($file)) {
                continue;
            }

            $this->files[$name] = $this->normalizeFile($file);
        }
    }

    /**
     * @param array<string, mixed> $files
     */
    public static function from(array $files): self
    {
        return new self($files);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->files);
    }

    public function get(string $name): UploadedFile|array|null
    {
        return $this->files[$name] ?? null;
    }

    public function file(string $name): ?UploadedFile
    {
        $file = $this->get($name);
        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function files(string $name): array
    {
        $file = $this->get($name);
        if ($file instanceof UploadedFile) {
            return [$file];
        }

        return is_array($file) ? $file : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawFiles;
    }

    public function count(): int
    {
        return count($this->files);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->files);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * @param array<string, mixed> $file
     * @return UploadedFile|array<int, UploadedFile>
     */
    private function normalizeFile(array $file): UploadedFile|array
    {
        if (isset($file["name"]) && is_array($file["name"])) {
            $files = [];
            foreach ($file["name"] as $key => $name) {
                $files[] = UploadedFile::fromArray([
                    "name" => $name,
                    "tmp_name" => $file["tmp_name"][$key] ?? "",
                    "size" => $file["size"][$key] ?? 0,
                    "error" => $file["error"][$key] ?? UPLOAD_ERR_NO_FILE,
                    "type" => $file["type"][$key] ?? "",
                ]);
            }
            return $files;
        }

        return UploadedFile::fromArray($file);
    }
}
