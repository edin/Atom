<?php

declare(strict_types=1);

namespace Atom\Http;

final readonly class UploadedFile
{
    public function __construct(
        public string $name,
        public string $tmpName,
        public int $size,
        public int $error,
        public string $type = ""
    ) {
    }

    /**
     * @param array{name?: mixed, tmp_name?: mixed, size?: mixed, error?: mixed, type?: mixed} $file
     */
    public static function fromArray(array $file): self
    {
        return new self(
            is_scalar($file["name"] ?? null) ? (string) $file["name"] : "",
            is_scalar($file["tmp_name"] ?? null) ? (string) $file["tmp_name"] : "",
            (int) ($file["size"] ?? 0),
            (int) ($file["error"] ?? UPLOAD_ERR_NO_FILE),
            is_scalar($file["type"] ?? null) ? (string) $file["type"] : ""
        );
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }
}
