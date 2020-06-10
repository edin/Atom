<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Container\Container;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use TypeError;

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
        $body = $this->request->getParsedBody();

        $content  = $this->request->getBody()->getContents();
        $jsonBody = json_decode($content, true);

        if (is_array($jsonBody)) {
            $body = $jsonBody;
        }

        foreach ($reflection->getProperties() as $prop) {
            try {
                $value = $body[$prop->name] ?? $params[$prop->name] ?? null;
                if ($value !== null) {
                    $prop->setValue($instance, $value);
                }
            } catch (Exception | TypeError $e) {
            }
        }
        return $instance;
    }
}
