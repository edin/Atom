<?php

declare(strict_types=1);

namespace Atom\View;

use Atom\Container;
use Atom\View\Ast\TemplateNode;
use Throwable;

final class Templates
{
    private static ?TemplateCache $fallback = null;

    /**
     * @param callable(): TemplateNode $factory
     */
    public static function remember(string $path, callable $factory): TemplateNode
    {
        return self::cache()->remember($path, $factory);
    }

    public static function cache(): TemplateCache
    {
        try {
            if (Container::has(TemplateCache::class)) {
                return Container::get(TemplateCache::class);
            }
        } catch (Throwable) {
        }

        return self::$fallback ??= new TemplateCache();
    }

    public static function reset(): void
    {
        self::$fallback = null;
    }
}
