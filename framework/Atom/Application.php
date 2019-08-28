<?php

namespace Atom;

use Atom\Container\Container;
use Atom\Router\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class Application
{
    public static $app = null;
    private $container = null;
    protected $baseUrl = "";

    public function __construct($configuration = [])
    {
        self::$app = $this;
    }

    final public function getContainer(): Container
    {
        if ($this->container == null) {
            $this->container = new Container();
        }
        return $this->container;
    }

    final public function getRouter(): Router
    {
        return $this->getContainer()->Router;
    }

    final public function getRequest(): RequestInterface
    {
        return $this->getContainer()->Request;
    }

    final public function getResponse(): ResponseInterface
    {
        return $this->getContainer()->Response;
    }

    final public function getDispatcher(): RequestHandlerInterface
    {
        return $this->getContainer()->Dispatcher;
    }

    public function registerDefaultServices()
    {
        $di = $this->getContainer();


        $di->Router = Router::class;
        $di->Application = $this;
        $di->bind(Container::class)->toInstance($di);

        $di->Request = function () {
            $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
            $creator = new \Nyholm\Psr7Server\ServerRequestCreator($factory, $factory, $factory, $factory);
            $serverRequest = $creator->fromGlobals();
            return $serverRequest;
        };

        $di->Response = function () {
            $factory = new Psr17Factory();
            return $factory->createResponse();
        };

        $di->ViewEngine = \Atom\View\ViewEngine::class;

        $di->{"Psr\Http\Message\RequestInterface"} = function () use ($di) {
            return $di->Request;
        };


        $di->{"Psr\Http\Message\ResponseInterface"} = function () use ($di) {
            return $di->Response;
        };

        $di->ViewEngine = \Atom\View\ViewEngine::class;
        $di->Dispatcher = \Atom\Dispatcher\Dispatcher::class;
        $di->ResultHandler = \Atom\Dispatcher\ResultHandler::class;
    }

    abstract public function registerRoutes(Router $router);
    abstract public function registerServices(Container $container);

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function resolveController($name)
    {
        return $this->getContainer()->resolveWithProperties($name);
    }

    public function initialize()
    {
        $this->registerDefaultServices();
        $this->registerServices($this->getContainer());
        $this->registerRoutes($this->getRouter());
    }

    public function run()
    {
        $this->initialize();
        $this->sendResponse($this->getDispatcher()->handle($this->getRequest()));
    }

    public function sendResponse(ResponseInterface $response)
    {
        $http_line = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($http_line, true, $response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }
        $stream = $response->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        while (!$stream->eof()) {
            echo $stream->read(1024 * 8);
        }
    }
}
