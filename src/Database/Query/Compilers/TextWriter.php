<?php

namespace Atom\Database\Query\Compilers;

class TextWriter 
{
    private $ident = 0;
    private $text = "";

    public function ident(): void 
    {
        $this->ident += 1;
    }

    public function unident(): void {
        if ($this->ident > 0) {
            $this->ident -= 1;
        }
    }

    public function write(string $text): void 
    {
        $line = str_repeat(" ", $this->ident * 4);
        $this->text .= $line + "\n";
    }

    public function getText(): string {
        return $this->text;
    }
}

