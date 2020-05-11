<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class Trim extends AbstractRule
{
    public function isValid($value): bool
    {
        if (is_string($value)) {
            $this->setResultValue(trim($value));
        } else {
            $this->setResultValue("");
        }
        return true;
    }
}
