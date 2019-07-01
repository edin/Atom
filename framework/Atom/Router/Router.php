<?php

namespace Atom\Router;

final class Router extends RouteGroup
{
    public function getAllRoutes(): array
    {
        //TODO: Collect child route groups

        $result = [];
        foreach ($this->getGroups() as $group) {
            foreach ($group->getRoutes() as $route) {
                $result[] = $route;
            }
        }
        return $result;
    }
}
