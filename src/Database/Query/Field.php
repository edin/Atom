<?php

namespace Atom\Database\Query;

final class Field
{
    private $table = null;
    private $name;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function getTableName(): ?string
    {
        return $this->table;
    }
}
