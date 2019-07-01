<?php

namespace Atom\Dispatcher;

use Atom\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Atom\Interfaces\IResultHandler;

trait ResultHandlerTrait {
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function getContainer() {
        return $this->container;
    }

    protected function getResponse() {
        return $this->container->Response;
    }
}


class ResultHandler
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    private function getContainer() {
        return $this->container;
    }

    private function getResponse() {
        return $this->container->Response;
    }

    public function process($result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        //TODO: Use IResultHandler registry

        if ($result instanceof \Atom\Interfaces\IViewInfo) {
            $view = $this->getContainer()->View;
            $content = $view->render($result);

            $response = $this->getResponse();
            $response->getBody()->write($content);
            return $response;
        }

        if (is_string($result)) {
            $response = $this->getResponse();
            $response->getBody()->write($result);
            return $response;
        }

        if (is_array($result) || is_object($result)) {
            $response = $response = $this->getResponse()->withAddedHeader("Content-Type", "application/json");
            $response->getBody()->write(json_encode($result));
            return $response;
        }

        $response = $this->getResponse();
        $response->getBody()->write((string) $result);
        return $response;
    }
}


class StringResultHandler implements IResultHandler
{
    use ResultHandlerTrait;

    public function isMatch($result): bool {
        return is_string($result);
    }

    public function process($result): ResponseInterface {
        $response = $this->getResponse();
        $response->getBody()->write($result);
        return $response;
    }
}

class ArrayResultHandler implements IResultHandler
{
    use ResultHandlerTrait;

    public function isMatch($result): bool {
        return is_array($result) || is_object($result);
    }

    public function process($result): ResponseInterface {
        $response = $this->getResponse()->withAddedHeader("Content-Type", "application/json");
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}


class ViewInfoResultHandler implements IResultHandler
{
    use ResultHandlerTrait;

    public function isMatch($result): bool {
        return $result instanceof \Atom\Interfaces\IViewInfo;
    }

    public function process($result): ResponseInterface {
        $view = $this->getContainer()->View;
        $content = $view->render($result);

        $response = $this->getResponse();
        $response->getBody()->write($content);
        return $response;
    }
}