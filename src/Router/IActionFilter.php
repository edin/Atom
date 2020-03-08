<?php

namespace Atom\Router;

interface IActionFilter
{
    public function filter(Action $action);
}
