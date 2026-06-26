<?php

declare(strict_types=1);

namespace Atom\Http;

use Stringable;

final class ContentStream implements ResponseBodyInterface, Stringable
{
    private string $content = "";

    public function __construct(string $content = "")
    {
        $this->content = $content;
    }

    public function write(string|Stringable $content): self
    {
        $this->content .= (string) $content;
        return $this;
    }

    public function line(string|Stringable $content = ""): self
    {
        $this->write($content);
        $this->content .= PHP_EOL;
        return $this;
    }

    public function replace(string|Stringable $content): self
    {
        $this->content = (string) $content;
        return $this;
    }

    public function clear(): self
    {
        $this->content = "";
        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->content === "";
    }

    public function length(): int
    {
        return strlen($this->content);
    }

    public function getContents(): string
    {
        return $this->content;
    }

    public function emit(): void
    {
        echo $this->content;
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
