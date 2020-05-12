<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class Ip extends AbstractRule
{
    protected string $errorMessage = "Value is not a valid IP address";

    public function isValid($value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_IP);
        $this->setResultValue($result);
        return $result !== false;
    }
}
