<?php

namespace Atom\Router;

interface IRouteBuilder
{
    public function build(RouteGroup $group);
}
