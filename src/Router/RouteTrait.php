<?php

declare(strict_types=1);

namespace Atom\Router;

trait RouteTrait
{
    private $name;
    private $title;
    private $description;
    private $path;
    private $group = null;
    private $middlewares = [];
    private $metadata = [];

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

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getFullPath(): string
    {
        $prefixPath = ($this->group) ? $this->group->getPath() : "";
        $prefixPath = rtrim($prefixPath, " /");
        $routePath  = ltrim($this->path, " /");

        if ($routePath != "") {
            $routePath  = "/" . $routePath;
        }

        $result = $prefixPath . $routePath;

        if ($result == "") {
            $result = "/";
        }

        return $result;
    }

    public function getGroup(): ?Router
    {
        return $this->group;
    }

    public function setGroup(Router $group): void
    {
        $this->group = $group;
    }

    public function middleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function metadata(object $instance): self
    {
        $this->metadata[] = $instance;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getMetadataOfType(string $typeName)
    {
        foreach ($this->metadata as $meta) {
            if (get_class($meta) === $typeName) {
                return $meta;
            }
        }
        return null;
    }

    public function getMetadataArrayOfType(string $typeName)
    {
        $result = array_filter($this->metadata, function ($item) use ($typeName) {
            return get_class($item) === $typeName;
        });
        return array_values($result);
    }

    public function getOwnMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getMiddlewares(): array
    {
        if ($this->group) {
            return \array_merge($this->group->getMiddlewares(), $this->middlewares);
        }
        return $this->middlewares;
    }
}
