<?php

namespace Atom\Router;

final class Router extends RouteGroup
{
    public function getAllRoutes(): array
    {
        $stack = new \SplStack;
        $result = [];

        foreach ($this->getGroups() as $group) {
            $stack->push($group);
        }

        while (!$stack->isEmpty()) {
            $group = $stack->pop();

            foreach ($group->getRoutes() as $route) {
                $result[] = $route;
            }

            foreach ($group->getGroups() as $group) {
                $stack->push($group);
            }
        }
        return $result;
    }
}
