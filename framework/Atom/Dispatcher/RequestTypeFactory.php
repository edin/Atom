<?php

namespace Atom\Dispatcher;

use Atom\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

class RequestTypeFactory
{
    public function __construct(Container $container, ServerRequestInterface $request)
    {
        $this->container = $container;
        $this->request = $request;
    }

    public function create(string $typeName)
    {
        // TODO: Inject dependencies - add method to container that directly creates type without resolvers
        $instance = new $typeName;

        // Assign values from query parameters
        $reflection = new \ReflectionClass($instance);
        $params = $this->request->getQueryParams();
        foreach($reflection->getProperties() as $prop) {
            $instance->{$prop->name} = $params[$prop->name] ?? "";
        }
        return $instance;
    }
}
