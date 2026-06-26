<?php

declare(strict_types=1);

namespace Atom\Router;

use Atom\Http\MiddlewareInterface;

final class RouteEntry
{
    private ?string $name = null;
    private ?string $title = null;
    private ?string $description = null;
    private string $path;
    private ?Router $router = null;
    /** @var array<class-string<MiddlewareInterface>|MiddlewareInterface> */
    private array $middlewares = [];
    /** @var object[] */
    private array $metadata = [];
    private string|array $method;
    private RouteAction $routeAction;

    private function __construct(string $path)
    {
        $this->path = $path;
    }

    public static function route(string|array $method, string $path, RouteAction $routeAction): self
    {
        $entry = new self($path);
        $entry->method = $method;
        $entry->routeAction = $routeAction;
        return $entry;
    }

    public function bindRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFullPath(): string
    {
        $prefixPath = rtrim($this->router?->getFullPath() ?? "", " /");
        $routePath  = ltrim($this->path, " /");

        if ($routePath != "") {
            $routePath  = "/" . $routePath;
        }

        $result = $prefixPath . $routePath;

        return $result == "" ? "/" : $result;
    }

    public function getRouter(): ?Router
    {
        return $this->router;
    }

    public function middleware(string|MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function metadata(object $instance): self
    {
        $this->metadata[] = $instance;
        return $this;
    }

    /**
     * @return object[]
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getMetadataOfType(string $typeName): ?object
    {
        foreach ($this->metadata as $meta) {
            if (get_class($meta) === $typeName) {
                return $meta;
            }
        }

        return null;
    }

    /**
     * @return object[]
     */
    public function getMetadataArrayOfType(string $typeName): array
    {
        $result = array_filter($this->metadata, function (object $item) use ($typeName): bool {
            return get_class($item) === $typeName;
        });

        return array_values($result);
    }

    /**
     * @return array<class-string<MiddlewareInterface>|MiddlewareInterface>
     */
    public function getOwnMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @return array<class-string<MiddlewareInterface>|MiddlewareInterface>
     */
    public function getMiddlewares(): array
    {
        return array_merge($this->router?->getMiddlewares() ?? [], $this->middlewares);
    }

    public function toController(string $controllerType, string $actionName): self
    {
        $this->routeAction = RouteAction::fromMethod($controllerType, $actionName);
        return $this;
    }

    public function toClosure(callable $closure): self
    {
        $this->routeAction = RouteAction::fromClosure($closure);
        return $this;
    }

    public function getMethod(): string|array
    {
        return $this->method;
    }

    public function getMethodList(): string
    {
        if (is_array($this->method)) {
            return implode("|", $this->method);
        } elseif (is_string($this->method)) {
            return $this->method;
        }

        return "";
    }

    public function getRouteAction(): RouteAction
    {
        return $this->routeAction;
    }

    public function getController(): ?string
    {
        return $this->getRouteAction()->controllerType;
    }

    public function getMethodName(): ?string
    {
        return $this->getRouteAction()->methodName;
    }

    public function getClosure(): ?callable
    {
        return $this->getRouteAction()->closure;
    }

    public function isClosure(): bool
    {
        return $this->getRouteAction()->isClosure();
    }
}
