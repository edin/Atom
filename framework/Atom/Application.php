<?php

namespace Atom;

use Atom\Container\Container;
use Atom\Router\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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

    final public function getRequest(): ServerRequestInterface
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
        $container = $this->getContainer();

        $container->Router = Router::class;
        $container->Application = $this;

        $container->bind(Container::class)->toInstance($container)->withName("Container");

        // TODO: Remove dependency from this file third party libraries
        $container->bind(ServerRequestInterface::class)->toFactory(function () {
            $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
            $creator = new \Nyholm\Psr7Server\ServerRequestCreator($factory, $factory, $factory, $factory);
            $serverRequest = $creator->fromGlobals();
            return $serverRequest;
        })->withName("Request");

        $container->bind(ResponseInterface::class)->toFactory(function () {
            $factory = new Psr17Factory();
            return $factory->createResponse();
        })->withName("Response");

        $container->ViewEngine = \Atom\View\ViewEngine::class;
        $container->Dispatcher = \Atom\Dispatcher\Dispatcher::class;
        $container->ResultHandler = \Atom\Dispatcher\ResultHandler::class;
    }

    abstract public function registerRoutes(Router $router);
    abstract public function registerServices(Container $container);

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function resolveController($name)
    {
        return $this->getContainer()->get($name);
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
