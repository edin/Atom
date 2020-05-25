<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

class RequestTypeFactory
{
    private Container $container;
    private ServerRequestInterface $request;

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
        $body   = $this->request->getBody()->getContents();
        $jsonBody = json_decode($body, true);

        if (is_array($jsonBody)) {
            $params = $jsonBody;
        }

        foreach ($reflection->getProperties() as $prop) {
            if (isset($params[$prop->name])) {
                $value = $params[$prop->name];
                $prop->setValue($instance, $value);
            }
        }
        return $instance;
    }
}
