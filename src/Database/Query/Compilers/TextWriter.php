<?php

declare(strict_types=1);

namespace Atom\Database\Query\Compilers;

class TextWriter
{
    private int $indent = 0;
    private string $text = "";

    public function indent(): void
    {
        $this->indent += 1;
    }

    public function unindent(): void
    {
        if ($this->indent > 0) {
            $this->indent -= 1;
        }
    }

    public function write(string $text): void
    {
        if (strpos($text, "\n") !== false) {
            $prefix = str_repeat(" ", $this->indent * 4);
            $text = str_replace("\n", "\n" . $prefix, $text);
        }
        $this->text .= $text;
    }

    public function clear(): void
    {
        $this->text = "";
        $this->indent = 0;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
