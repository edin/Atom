<?php

namespace Atom\Router;

final class Router {

    private $groups = [];

    public function addGroup($path = ""): RouteGroup {
        $group = new RouteGroup();
        $group->setPrefixPath($path);
        $this->groups[] = $group;
        return $group;
    }

    public function getGroups(): array  {
        return $this->groups;
    }

    public function getAllRoutes(): array {
        $result = [];

        foreach($this->getGroups() as $group) {
            foreach($group->getRoutes() as $route) {
                $result[] = $route;
            }
        }

        return $result;
    }
}