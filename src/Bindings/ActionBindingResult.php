<?php

namespace Atom\Bindings;

final class ActionBindingResult
{
    /**
     * @var BindingParameter[]
     */
    public $parameters = [];

    public array $arguments = [];

    public array $missing = [];
}
