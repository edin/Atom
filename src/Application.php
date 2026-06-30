<?php

declare(strict_types=1);

namespace Atom;

use Atom\Config\Config;
use Atom\Config\Env;
use Atom\Config\Options;
use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderRegistry;
use Atom\Di\TypeFactory;
use Atom\Di\TypeInfo;
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
use Atom\Module\ModuleRegistry;
use Atom\Page\PageRegistry;
use Atom\Page\PageRouteRegistrar;
use Atom\Page\PageServices;
use Atom\Router\MatchedRoute;
use Atom\Router\Route;
use Atom\Router\Router;
use Atom\Support\Paths;
use Atom\View\Component\ComponentRegistry;
use RuntimeException;

abstract class Application
{
    public static ?Application $app = null;
    private ?ServiceProviderRegistry $providers = null;
    private ?Config $config = null;
    private ?Paths $paths = null;
    private ?ModuleRegistry $modules = null;
    private ?PageRegistry $pages = null;
    private ?ApplicationBootstrappers $bootstrappers = null;
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

    final public function getConfig(): Config
    {
        return $this->config ?? throw new RuntimeException("Application has not been initialized.");
    }

    final public function getPaths(): Paths
    {
        return $this->paths ?? throw new RuntimeException("Application has not been initialized.");
    }

    final public function getBindings(): Bindings
    {
        return $this->bindings ?? throw new RuntimeException("Application has not been initialized.");
    }

    final public function getModules(): ModuleRegistry
    {
        return $this->modules ?? throw new RuntimeException("Application has not been initialized.");
    }

    final public function getPages(): PageRegistry
    {
        return $this->pages ?? throw new RuntimeException("Application has not been initialized.");
    }

    final public function getBootstrappers(): ApplicationBootstrappers
    {
        return $this->bootstrappers ?? throw new RuntimeException("Application has not been initialized.");
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
            $basePath,
            $this->getBindings(),
            $this->getConfig()
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

    protected function modules(ModuleRegistry $modules): void
    {
    }

    protected function components(ComponentRegistry $components): void
    {
    }

    protected function pages(PageRegistry $pages): void
    {
    }

    protected function configurePaths(Paths $paths): void
    {
    }

    protected function rootPath(): string
    {
        $file = (new \ReflectionClass($this))->getFileName();
        if ($file === false) {
            throw new RuntimeException("Cannot determine application root path.");
        }

        return dirname($file);
    }

    protected function configure(Config $config): void
    {
    }

    /**
     * @return list<string>
     */
    protected function environmentFiles(): array
    {
        return ["@root/.env"];
    }

    protected function bootstrap(Injector $injector): void
    {
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    final protected function path(string $path): string
    {
        return $this->getPaths()->resolve($path);
    }

    final public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $root = $this->rootPath();
        $paths = new Paths($root);
        $paths->alias("app", $root . "/app");
        $this->paths = $paths;
        $this->configurePaths($paths);

        foreach ($this->environmentFiles() as $path) {
            Env::loadIfExists($paths->resolve($path));
        }

        $config = Config::fromEnv();
        $this->config = $config;
        $this->configure($config);

        $providers = ServiceProviderRegistry::create();
        $this->registerDefaultServices($providers);
        $this->services($providers);

        $bindings = $providers->bindings();
        $bindings->value(Application::class, $this);
        $bindings->value(Paths::class, $paths);
        $bindings->value(Config::class, $config);
        $bindings->addTypeFactory(TypeFactory::match(
            fn(TypeInfo $type): bool => $type->hasAttribute(Options::class),
            fn(string $className, Injector $injector, InjectionContext $context): object =>
                $injector->get(Config::class, $context)->options($className)
        ));
        if (static::class !== Application::class) {
            $bindings->value(static::class, $this);
        }
        $bindings->value(ServiceProviderRegistry::class, $providers);
        $bindings->value(Bindings::class, $bindings);

        $this->providers = $providers;
        $this->bindings = $bindings;
        $this->injector = new Injector($bindings);
        $this->modules = new ModuleRegistry();
        $this->pages = new PageRegistry();
        $this->bootstrappers = new ApplicationBootstrappers();

        Route::setRouter($this->injector->get(Router::class));

        $this->modules($this->modules);
        foreach ($this->modules->all() as $registration) {
            $this->registerModule($registration->module, $registration->basePath);
        }

        $this->components($this->injector->get(ComponentRegistry::class));

        $this->pages($this->pages);
        $pageRegistrar = new PageRouteRegistrar();
        foreach ($this->pages->directories() as $directory) {
            $pageRegistrar->registerDirectory(
                $this->getRouter(),
                $paths->resolve($directory->directory),
                $directory->pathPrefix
            );
        }

        foreach ($providers->providers() as $provider) {
            if ($provider instanceof ApplicationBootstrapperProviderInterface) {
                $provider->bootstrappers($this->bootstrappers);
            }
        }

        foreach ($this->bootstrappers->all() as $bootstrapper) {
            $bootstrapper->bootstrap($this->injector);
        }

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
