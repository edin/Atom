<?php

declare(strict_types=1);

namespace Atom;

use Atom\Router\Route;
use Atom\Router\Router;
use Atom\View\ViewServices;
use Atom\Container\Container;
use Atom\Dispatcher\DispatcherServices;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

abstract class Application
{
    public static $app = null;
    private $container = null;
    private $plugins = [];
    private $pluginInstances = [];
    private $baseUrl = "";

    public function __construct()
    {
        if (self::$app !== null) {
            throw new RuntimeException("Application was already initialized");
        }
        static::$app = $this;

        $this->container = new Container();
        $this->container->bind(Application::class)->toInstance($this);
        $this->container->bind(static::class)
            ->withName("Application")
            ->toInstance($this);

        $this->container->bind(Container::class)
            ->withName("Container")
            ->toInstance($this->container);
    }

    final public function getContainer(): Container
    {
        return $this->container;
    }

    final public function getRouter(): Router
    {
        return $this->container->Router;
    }

    final public function getCurrentRoute(): ?Route
    {
        return $this->container->CurrentRoute;
    }

    final public function getRequest(): ServerRequestInterface
    {
        return $this->container->Request;
    }

    final public function getResponse(): ResponseInterface
    {
        return $this->container->Response;
    }

    final public function getDispatcher(): RequestHandlerInterface
    {
        return $this->container->Dispatcher;
    }

    final public function getResponseEmitter()
    {
        return $this->container->ResponseEmitter;
    }

    public function registerDefaultServices()
    {
        $this->container->bind(Router::class)
            ->withName("Router")
            ->toSelf()
            ->asShared();

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

    public function initialize()
    {
        $this->registerDefaultServices();
        $this->configure();

        foreach ($this->plugins as $pluginType) {
            $this->pluginInstances[] = $this->container->createType($pluginType);
        }

        foreach ($this->pluginInstances as $plugin) {
            $reflection = new \ReflectionClass($plugin);
            if ($reflection->hasMethod("configureServices")) {
                $configureServices = $reflection->getMethod("configureServices");
                $parameters =  $this->container->resolveMethodParameters($configureServices);
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
