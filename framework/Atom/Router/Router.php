<?php

namespace Atom\Router;

final class Router {

    private $groups = [];

    public function addGroup(): RouteGroup {
        $group = new RouteGroup();
        $this->groups[] = $group;
        return $group;
    }

    public function getGroups(): array  {
        return $this->groups;
    }
}