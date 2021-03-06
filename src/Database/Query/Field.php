<?php

declare(strict_types=1);

namespace Atom\Database\Query;

final class Field
{
    public ?string $table = null;
    public string $name;

    public function __construct(string $name)
    {
        $parts = explode(".", $name, 2);
        if (count($parts) == 2) {
            $this->table = $parts[0];
            $this->name = $parts[1];
        } else {
            $this->name = $name;
        }
    }

    public static function from(string $name): self
    {
        return new Field($name);
    }
}
