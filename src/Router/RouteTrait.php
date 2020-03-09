<?php

namespace Atom\Router;

trait RouteTrait
{
    private $name;
    private $title;
    private $description;
    private $path;
    private $group;
    private $middlewares = [];
    private $metadata = [];
    private $actionFilters = [];

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function withDescription(string $description): self
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

    public function addMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
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
        $result = [];
        foreach ($this->meta as $meta) {
            if (get_class($meta) === $typeName) {
                $result[] = $meta;
            }
        }
        return $result;
    }

    public function addMetadata(object $instance): self
    {
        $this->metadata[] = $instance;
        return $this;
    }

    public function getOwnMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function setGroup(RouteGroup $group)
    {
        $this->group = $group;
    }

    public function getGroup(): ?RouteGroup
    {
        return $this->group;
    }

    public function getMiddlewares(): array
    {
        if ($this->group) {
            return \array_merge($this->group->getMiddlewares(), $this->middlewares);
        }
        return $this->middlewares;
    }

    public function addActionFilter($actionFilter): self
    {
        $this->actionFilters[] = $actionFilter;
        return $this;
    }

    public function getOwnActionFilters(): array
    {
        return $this->actionFilters;
    }

    public function getActionFilters(): array
    {
        if ($this->group) {
            return \array_merge($this->group->getActionFilters(), $this->actionFilters);
        }
        return $this->actionFilters;
    }
}
