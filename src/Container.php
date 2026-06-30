<?php

declare(strict_types=1);

namespace Atom;

use RuntimeException;

final readonly class Container
{
    public static function get(string $type): mixed
    {
        return self::app()->getInjector()->get($type);
    }

    public static function has(string $type): bool
    {
        return Application::$app?->getInjector()->has($type) ?? false;
    }

    private static function app(): Application
    {
        return Application::$app ?? throw new RuntimeException("Application has not been initialized.");
    }
}
