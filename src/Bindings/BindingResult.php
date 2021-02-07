<?php

namespace Atom\Bindings;

final class BindingResult
{
    /**
     * @var mixed $value
     */
    public $value;

    /**
     * @var mixed $value
     */
    public $message = null;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
