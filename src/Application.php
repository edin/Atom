<?php

declare(strict_types=1);

namespace Atom;

use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderRegistry;
use Atom\Dispatcher\Dispatcher;
use Atom\Dispatcher\DispatcherServices;
use Atom\Dispatcher\ResponseEmitterInterface;
use Atom\Console\ConsoleApplication;
use Atom\Console\ConsoleServices;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Http\RequestHandlerInterface;
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Page\PageServices;
use Atom\Router\MatchedRoute;
use Atom\Router\Route;
use Atom\Router\Router;
use Atom\View\Component\ComponentRegistry;
use RuntimeException;

abstract class Application
{
    public static ?Application $app = null;
    private ?ServiceProviderRegistry $providers = null;
    private ?Bindings $bindings = null;
    private ?Injector $injector = null;
    private ?InjectionContext $currentContext = null;
    private bool $initialized = false;
    protected string $baseUrl = "";

    public function __construct()
    {
        if (self::$app !== null) {
            throw new RuntimeException("Application was already initialized");
        }
        static::$app = $this;
    }

    final public function getProviders(): ServiceProviderRegistry
    {
        return $this->providers ?? throw new RuntimeException("Application has not been initialized.");
    }

    final public function getBindings(): Bindings
    {
        return $this->bindings ?? throw new RuntimeException("Application has not been initialized.");
    }

    final public function getInjector(): Injector
    {
        return $this->injector ?? throw new RuntimeException("Application has not been initialized.");
    }

    final public function getRouter(): Router
    {
        return $this->getInjector()->get(Router::class, $this->currentContext);
    }

    final public function getCurrentRoute(): ?MatchedRoute
    {
        $route = $this->currentContext?->get(MatchedRoute::class);
        return $route instanceof MatchedRoute ? $route : null;
    }

    final public function getRequest(): Request
    {
        return $this->getInjector()->get(Request::class, $this->currentContext);
    }

    final public function getResponse(): Response
    {
        return $this->getInjector()->get(Response::class, $this->currentContext);
    }

    final public function getDispatcher(): RequestHandlerInterface
    {
        return $this->getInjector()->get(Dispatcher::class, $this->currentContext);
    }

    final public function getResponseEmitter(): ResponseEmitterInterface
    {
        return $this->getInjector()->get(ResponseEmitterInterface::class, $this->currentContext);
    }

    final public function getConsole(): ConsoleApplication
    {
        return $this->getInjector()->get(ConsoleApplication::class);
    }

    final public function registerModule(ModuleInterface $module, string $basePath = ""): void
    {
        $module->register(new ModuleContext(
            $this->getRouter(),
            $this->getInjector(),
            $this->getInjector()->get(ComponentRegistry::class),
            $basePath
        ));
    }

    final protected function registerDefaultServices(ServiceProviderRegistry $providers): void
    {
        $providers
            ->add(ConsoleServices::class)
            ->add(DispatcherServices::class)
            ->add(PageServices::class);
    }

    protected function services(ServiceProviderRegistry $providers): void
    {
    }

    protected function bootstrap(Injector $injector): void
    {
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    final public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $providers = ServiceProviderRegistry::create();
        $this->registerDefaultServices($providers);
        $this->services($providers);

        $bindings = $providers->bindings();
        $bindings->value(Application::class, $this);
        if (static::class !== Application::class) {
            $bindings->value(static::class, $this);
        }
        $bindings->value(ServiceProviderRegistry::class, $providers);
        $bindings->value(Bindings::class, $bindings);

        $this->providers = $providers;
        $this->bindings = $bindings;
        $this->injector = new Injector($bindings);

        Route::setRouter($this->injector->get(Router::class));
        $this->bootstrap($this->injector);

        $this->initialized = true;
    }

    final public function run(?Request $request = null): Response
    {
        $this->initialize();

        $this->currentContext = new InjectionContext();
        if ($request !== null) {
            $this->currentContext->set(Request::class, $request);
        }

        $response = $this->getDispatcher()->handle($this->getRequest());
        $this->getResponseEmitter()->emit($response);

        return $response;
    }
}
