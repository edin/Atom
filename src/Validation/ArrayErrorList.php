<?php

namespace Atom\Validation;

class ArrayErrorList 
{
    public $type = "Array";
    public $errors = [];
 
    public function addError($key, ErrorMessage $message) 
    {
        $this->errors[$key] = $message;
    }
}