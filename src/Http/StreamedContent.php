<?php

declare(strict_types=1);

namespace Atom\Http;

final readonly class StreamedContent implements ResponseBodyInterface
{
    /**
     * @param callable(callable(string): void): void $stream
     */
    public function __construct(private mixed $stream)
    {
        if (!is_callable($stream)) {
            throw new \InvalidArgumentException("Streamed content must be callable.");
        }
    }

    public function emit(): void
    {
        ($this->stream)(static function (string $content): void {
            echo $content;
        });
    }

    public function getContents(): string
    {
        return "";
    }
}
