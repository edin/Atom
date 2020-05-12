<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class Url extends AbstractRule
{
    protected string $errorMessage = "The field value should be valid url";

    public function isValid($value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_URL);
        $this->setResultValue($result);
        return $result !== false;
    }
}
