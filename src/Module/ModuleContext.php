<?php

declare(strict_types=1);

namespace Atom\Module;

use Atom\Config\Config;
use Atom\Console\ConsoleCommandSources;
use Atom\Di\BindingBuilder;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Http\StaticFileHandler;
use Atom\Http\StaticFileRouteMetadata;
use Atom\Http\MiddlewareInterface;
use Atom\Page\PageRouteRegistrar;
use Atom\Queue\JobInterface;
use Atom\Queue\JobRegistry;
use Atom\Router\RouteEntry;
use Atom\Router\Router;
use Atom\Scheduler\Schedule;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use Atom\View\Component\ComponentSet;

final readonly class ModuleContext
{
    public Router $router;
    public Injector $injector;
    public ComponentRegistry $components;
    public string $basePath;
    public Bindings $bindings;
    public Config $config;

    public function __construct(
        Router $router,
        Injector $injector,
        ComponentRegistry $components,
        string $basePath = "",
        ?Bindings $bindings = null,
        ?Config $config = null
    ) {
        $this->router = $router;
        $this->injector = $injector;
        $this->components = $components;
        $this->basePath = $basePath;
        $this->bindings = $bindings ?? Bindings::create();
        $this->config = $config ?? Config::fromEnv();
    }

    public function withBasePath(string $basePath): self
    {
        return new self($this->router, $this->injector, $this->components, $basePath, $this->bindings, $this->config);
    }

    public function root(): self
    {
        return $this->withBasePath("");
    }

    public function mountedPath(string $relativePath = ""): string
    {
        $base = rtrim($this->basePath, " /");
        $relativePath = trim($relativePath, " /");
        $segments = array_filter([$base, $relativePath], static fn(string $segment): bool => $segment !== "");

        return "/" . implode("/", array_map(static fn(string $segment): string => trim($segment, " /"), $segments));
    }

    public function resourcePath(string $path = "/resources", string $file = ""): string
    {
        return $this->mountedPath($this->joinRelative($path, $file));
    }

    public function route(RouteEntry $entry): RouteEntry
    {
        return $this->router->add($entry);
    }

    public function bind(string $token): BindingBuilder
    {
        return $this->bindings->bind($token);
    }

    /**
     * @param class-string<ComponentInterface> $className
     */
    public function component(string $name, string $className): self
    {
        $this->components->register($name, $className);

        return $this;
    }

    public function importComponents(ComponentSet $set): self
    {
        $this->components->import($set);

        return $this;
    }

    public function commands(string $directory, string $namespace): self
    {
        $this->injector
            ->get(ConsoleCommandSources::class)
            ->add($directory, $namespace);

        return $this;
    }

    /**
     * @param class-string<JobInterface> ...$jobs
     */
    public function jobs(string ...$jobs): self
    {
        $registry = $this->injector->get(JobRegistry::class);
        foreach ($jobs as $job) {
            $registry->register($job);
        }

        return $this;
    }

    /** @param callable(Schedule): void $definition */
    public function schedule(callable $definition): self
    {
        $definition($this->injector->get(Schedule::class));
        return $this;
    }

    /**
     * @param array<class-string<MiddlewareInterface>|MiddlewareInterface> $middlewares
     * @return RouteEntry[]
     */
    public function pages(string $directory, array $middlewares = []): array
    {
        return (new PageRouteRegistrar())->registerDirectory(
            $this->router,
            $directory,
            $this->basePath,
            $middlewares
        );
    }

    /**
     * @return RouteEntry[]
     */
    public function resources(string $path, string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $routePath = $this->resourcePath($path, "{path*}");
        foreach ($this->router->getAllRoutes() as $route) {
            if ($route->getFullPath() === $routePath && $route->getMethod() === "GET") {
                return [$route];
            }
        }

        $entry = RouteEntry::create(
            "GET",
            $routePath,
            [StaticFileHandler::class, "serve"]
        )->metadata(new StaticFileRouteMetadata($directory));

        $this->route($entry);

        return [$entry];
    }

    private function joinRelative(string $path, string $relativePath): string
    {
        $path = trim($path, " /");
        $relativePath = ltrim($relativePath, " /");
        $segments = array_filter([$path, $relativePath], static fn(string $segment): bool => trim($segment, " /") !== "");

        return implode("/", array_map(static fn(string $segment): string => trim($segment, " /"), $segments));
    }

}
