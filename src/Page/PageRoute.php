<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Http\MiddlewareInterface;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class PageRoute
{
    public string $path;
    public ?string $name;
    public ?string $title;
    public ?string $description;
    /** @var array<class-string<MiddlewareInterface>|MiddlewareInterface> */
    public array $middlewares;

    /**
     * @param class-string<MiddlewareInterface>|MiddlewareInterface|array<class-string<MiddlewareInterface>|MiddlewareInterface> $middleware
     */
    public function __construct(
        string $path,
        ?string $name = null,
        string|MiddlewareInterface|array $middleware = [],
        ?string $title = null,
        ?string $description = null
    ) {
        $this->path = $path;
        $this->name = $name;
        $this->middlewares = is_array($middleware) ? array_values($middleware) : [$middleware];
        $this->title = $title;
        $this->description = $description;
    }
}
