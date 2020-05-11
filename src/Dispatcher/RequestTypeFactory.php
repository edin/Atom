<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

class RequestTypeFactory
{
    private $container;
    private $request;

    public function __construct(Container $container, ServerRequestInterface $request)
    {
        $this->container = $container;
        $this->request = $request;
    }

    public function create(string $typeName)
    {
        $instance = $this->container->createType($typeName);

        $reflection = new \ReflectionClass($instance);
        $params = $this->request->getQueryParams();

        foreach ($reflection->getProperties() as $prop) {
            $instance->{$prop->name} = $params[$prop->name] ?? "";
        }
        return $instance;
    }
}
