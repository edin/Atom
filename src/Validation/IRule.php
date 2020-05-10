<?php

namespace Atom\Validation;

interface IRule
{
    public function setErrorMessage(string $errorMessage): void;
    public function validate($value): RuleResult;
}
