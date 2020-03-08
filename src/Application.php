<?php

namespace Atom;

use Atom\Router\Router;
use Atom\View\ViewServices;
use Atom\Container\Container;
use Atom\Dispatcher\DispatcherServices;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class Application
{
    public static $app = null;
    private $container = null;
    protected $baseUrl = "";
    private $plugins = [];
    private $pluginInstances = [];

    public function __construct()
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

    final public function getResponseEmitter()
    {
        return $this->getContainer()->ResponseEmitter;
    }

    public function registerDefaultServices()
    {
        $container = $this->getContainer();
        $container->Application = $this;
        $container->Router = Router::class;
        $container->bind(Container::class)
            ->withName("Container")
            ->toInstance($container);

        $this->use(DispatcherServices::class);
        $this->use(ViewServices::class);
    }

    abstract public function configure();

    public function use($plugin): void
    {
        $this->plugins[] = $plugin;
    }

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
        $container = $this->getContainer();
        $this->registerDefaultServices();
        $this->configure();

        foreach ($this->plugins as $pluginType) {
            $this->pluginInstances[] =  $container->createType($pluginType);
        }

        foreach ($this->pluginInstances as $plugin) {
            $reflection = new \ReflectionClass($plugin);
            if ($reflection->hasMethod("configureServices")) {
                $configureServices = $reflection->getMethod("configureServices");
                $parameters =  $container->resolveMethodParameters($configureServices);
                $configureServices->invokeArgs($plugin, $parameters);
            }
        }

        foreach ($this->pluginInstances as $plugin) {
            $reflection = new \ReflectionClass($plugin);
            if ($reflection->hasMethod("configure")) {
                $configure = $reflection->getMethod("configure");
                $parameters =  $this->container->resolveMethodParameters($configure);
                $configure->invokeArgs($plugin, $parameters);
            }
        }
    }

    public function run()
    {
        $this->initialize();
        $response = $this->getDispatcher()->handle($this->getRequest());
        $this->getResponseEmitter()->emit($response);
    }
}
