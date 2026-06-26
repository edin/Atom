<?php

declare(strict_types=1);

namespace Atom\Validation;

interface ValidationRuleInterface
{
    public function validate(mixed $value, ValidationContext $context): ?ValidationError;
}

