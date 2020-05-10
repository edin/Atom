<?php

namespace Atom\Validation;

class ErrorMessage 
{
    public $attributes;
    public $message;

    public function __construct(string $message, array $attributes)
    {
        $this->message = $message;
        $this->attributes = $attributes;
    }
}