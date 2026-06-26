<?php

declare(strict_types=1);

namespace Atom\Validation;

use Closure;

final class Validation extends Schema
{
    public function __get(string $name): Field
    {
        return $this->field($name);
    }

    public static function create(Closure $builder): self
    {
        $validation = new self();
        $builder($validation);
        return $validation;
    }
}

